<?php

namespace Modules\MarketingAddon\App\Services;

use Modules\MarketingAddon\App\Models\Template;
use Modules\MarketingAddon\App\Models\MessageReport;
use Modules\MarketingAddon\App\Jobs\SendNotificationJob;
use Modules\MarketingAddon\App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyService
{
    public function send(string $event, array $data, string $businessId)
    {
        $settings = get_option('notification_settings_' . $businessId);

        if (!isset($settings[$event])) {
            return;
        }

        $array = [];

        foreach (['sms', 'whatsapp', 'email'] as $channel) {

            // Check enabled in settings
            if (empty($settings[$event][$channel]['enabled'])) {
                continue;
            }

            // Check required data per channel
            if (in_array($channel, ['sms', 'whatsapp']) && empty($data['phone'])) {
                continue;
            }

            if ($channel === 'email' && empty($data['email'])) {
                continue;
            }

            $array[] = $channel;
            $template = Template::where('business_id', $businessId)->where('title', $event)->where('type', $channel)->first();

            if (empty($template)) {
                continue;
            }

            $notification = MessageReport::create([
                                'title'       => $event,
                                'channel'     => $channel,
                                'business_id' => $businessId,
                                'template_id' => $template->id,
                                'message'     => $template->message,
                            ]);

            // WhatsApp is sent immediately because this hosting account has no
            // long-running Laravel queue worker. The OmniReach API queues delivery
            // itself, while the sale remains successful if the gateway is unavailable.
            if ($channel === 'whatsapp') {
                try {
                    app(WhatsAppService::class)->send(
                        phone: $data['phone'],
                        template: $template,
                        businessId: (int) $businessId,
                        name: $data['name'] ?? '',
                        data: $data,
                    );

                    $notification->update([
                        'status' => 'success',
                        'send_at' => now(),
                    ]);
                } catch (Throwable $e) {
                    $notification->update([
                        'status' => 'failed',
                        'send_at' => now(),
                    ]);

                    Log::error('WhatsApp notification failed', [
                        'business_id' => $businessId,
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                continue;
            }

            // SMS and email continue through the background queue.
            SendNotificationJob::dispatch(
                data: $data,
                channel: $channel,
                template: $template,
                businessId: $businessId,
                notificationId: $notification->id,
            )->onQueue('high');
        }

        return $array;
    }
}
