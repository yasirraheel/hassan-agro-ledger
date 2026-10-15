<div class="tab-pane fade" id="cron-job" role="tabpanel" aria-labelledby="cron-job-tab">
                                <div class="order-form-section p-16">
                                    <div class="cron-guide-wrapper">
                                        <div class="cron-guide-hero">
                                            <div>
                                                <span class="cron-status-pill">
                                                    <span class="cron-dot"></span>
                                                    {{ __('MarketingAddon is available') }}
                                                </span>
                                                <h4 class="cron-guide-title">{{ __('Cron Job & Queue Worker Setup') }}</h4>
                                                <p class="cron-guide-copy">
                                                    {{ __('Use a queue worker to process background jobs such as campaign messages, reminders, email, SMS, and WhatsApp notifications without slowing down the dashboard.') }}
                                                </p>
                                            </div>

                                            <div class="cron-status-card">
                                                <h5 class="mb-2">{{ __('Queue command') }}</h5>
                                                <p class="text-muted mb-3">{{ __('Use the command below for VPS/Dedicated servers with Supervisor. For shared hosting, use the schedule:run command in the Shared Hosting section.') }}</p>
                                                <pre class="cron-command">php artisan queue:work --queue=high,medium,low --sleep=3 --tries=3 --timeout=60</pre>
                                            </div>
                                        </div>

                                        <div class="cron-guide-grid">
                                            <div class="cron-guide-card">
                                                <h5>{{ __('Shared Hosting') }}</h5>
                                                <ul class="cron-step-list">
                                                    <li>
                                                        <span class="cron-step-number">1</span>
                                                        <span>{{ __('Open cPanel or your hosting control panel and search for Cron Jobs.') }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="cron-step-number">2</span>
                                                        <span>{{ __('Set the schedule to run every minute. In many panels this is shown as Once Per Minute or five star symbols.') }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="cron-step-number">3</span>
                                                        <span>{{ __('Use the schedule:run command below. This will automatically trigger the queue worker every minute.') }}</span>
                                                    </li>
                                                </ul>
                                                <div class="mt-3">
                                                    <pre class="cron-command">cd /home/USERNAME/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1</pre>
                                                </div>
                                                <div class="cron-note">
                                                    {{ __('The schedule:run command checks every minute if any scheduled tasks are due, then runs them. It automatically executes queue:work with the correct queue priority. On shared hosting, the worker runs for 60 seconds then exits, and a new instance starts when cron triggers schedule:run again after 1 minute. Common PHP paths are /usr/local/bin/php, /opt/cpanel/ea-php82/root/usr/bin/php, or simply php.') }}
                                                </div>
                                            </div>

                                            <div class="cron-guide-card">
                                                <h5>{{ __('VPS / Dedicated Server') }}</h5>
                                                <ul class="cron-step-list">
                                                    <li>
                                                        <span class="cron-step-number">1</span>
                                                        <span>{{ __('Run the queue worker directly from SSH to confirm the high, medium, and low queues are processing.') }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="cron-step-number">2</span>
                                                        <span>{{ __('After testing, install Supervisor and keep the same worker command running in the background.') }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="cron-step-number">3</span>
                                                        <span>{{ __('Restart Supervisor after changing queue, mail, or notification settings.') }}</span>
                                                    </li>
                                                </ul>
                                                <div class="mt-3">
                                                    <pre class="cron-command">cd /var/www/your-project && php artisan queue:work --queue=high,medium,low --sleep=3 --tries=3 --timeout=60</pre>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cron-guide-card mt-3">
                                            <h5>{{ __('Supervisor configuration') }}</h5>
                                            <p class="text-muted">{{ __('Create this file on your VPS, then replace the path, user, and PHP binary for your server.') }}</p>
                                            <div class="cron-supervisor-file">
                                                <pre class="cron-command">sudo nano /etc/supervisor/conf.d/pospro-worker.conf</pre>
                                                <pre class="cron-command">[program:pospro-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-project/artisan queue:work --queue=high,medium,low --sleep=3 --tries=3 --timeout=60
directory=/var/www/your-project
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-project/storage/logs/worker.log
stopwaitsecs=3600</pre>
                                                <pre class="cron-command">sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart pospro-worker:*</pre>
                                            </div>
                                            <div class="cron-note">
                                                {{ __('For MarketingAddon campaigns, keep the queue worker alive continuously. The command should include --queue=high,medium,low so urgent jobs are processed before normal and low priority jobs.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>