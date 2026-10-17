import 'package:flutter/material.dart';
import 'dart:convert';

import 'offline_service.dart';
import '../Screens/Authentication/Sign In/sign_in_screen.dart';

class SyncStatusBar extends StatelessWidget {
  const SyncStatusBar({super.key});

  @override
  Widget build(BuildContext context) {
    final service = OfflineService.instance;
    return ListenableBuilder(
      listenable: service,
      builder: (context, _) {
        if (!service.hasSession) return const SizedBox.shrink();
        final warning = !service.online || service.conflictCount > 0;
        final color = warning ? const Color(0xff9a5b08) : const Color(0xff087f67);
        return Material(
          color: warning ? const Color(0xfffff4df) : const Color(0xffeaf7f2),
          child: InkWell(
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SyncStatusScreen())),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
              child: Row(
                children: [
                  Icon(
                    service.syncing
                        ? Icons.sync_rounded
                        : service.online
                        ? Icons.cloud_done_outlined
                        : Icons.cloud_off_rounded,
                    size: 18,
                    color: color,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      service.conflictCount > 0
                          ? '${service.conflictCount} changes need review'
                          : service.pendingCount > 0
                          ? '${service.pendingCount} changes saved · waiting to sync'
                          : service.downloading
                          ? 'Downloading business data…'
                          : !service.online
                          ? 'Offline · using saved data'
                          : !service.ready
                          ? 'Prepare offline data'
                          : 'Synced · saved on this device',
                      style: TextStyle(color: color, fontSize: 12),
                      maxLines: 2,
                    ),
                  ),
                  Icon(Icons.chevron_right_rounded, color: color, size: 18),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class SyncStatusButton extends StatelessWidget {
  final Future<void> Function() onSync;
  const SyncStatusButton({super.key,required this.onSync});
  @override
  Widget build(BuildContext context) {
    final service = OfflineService.instance;
    return ListenableBuilder(listenable:service,builder:(context,_) => GestureDetector(
      onLongPress:() => Navigator.push(context,MaterialPageRoute(builder:(_) => const SyncStatusScreen())),
      child:IconButton(
      tooltip:service.conflictCount>0 ? '${service.conflictCount} changes need review' : service.pendingCount>0 ? '${service.pendingCount} changes waiting to sync' : service.online ? 'Sync status' : 'Offline · using saved data',
      onPressed:service.syncing?null:onSync,
      icon:Badge(isLabelVisible:service.pendingCount>0 || service.conflictCount>0,
        label:Text('${service.pendingCount+service.conflictCount}'),
        child:Icon(Icons.refresh,
          size:21,color:service.conflictCount>0 || !service.online?const Color(0xff9a5b08):const Color(0xff087f67))),
    )));
  }
}

class SyncStatusScreen extends StatelessWidget {
  const SyncStatusScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final service = OfflineService.instance;
    return Scaffold(
      appBar: AppBar(title: const Text('Connection & saved work')),
      body: ListenableBuilder(
        listenable: service,
        builder: (context, _) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Icon(
              service.online ? Icons.cloud_done_rounded : Icons.cloud_off_rounded,
              size: 64,
              color: const Color(0xff087f67),
            ),
            const SizedBox(height: 20),
            Text(service.status, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 12),
            Text(
              service.lastSync == null
                  ? 'Initial download has not completed.'
                  : 'Last successful sync: ${service.lastSync!.toLocal().toString().split('.').first}',
            ),
            const SizedBox(height: 8),
            Text('${service.pendingCount} pending · ${service.conflictCount} need review'),
            if (service.lastError != null)
              Padding(padding: const EdgeInsets.only(top: 12), child: Text(service.lastError!)),
            if (service.downloading)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: LinearProgressIndicator(value: service.progress),
              ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: service.syncing ? null : () => service.sync(force: true),
              icon: const Icon(Icons.sync),
              label: const Text('Sync now'),
            ),
            if (service.connection == ServerConnection.signInRequired)
              TextButton(
                onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const SignIn())),
                child: const Text('Sign in to reconnect'),
              ),
            if (service.conflictCount > 0)
              OutlinedButton(
                onPressed: service.syncing ? null : service.retryConflicts,
                child: const Text('Retry unchanged requests'),
              ),
            const SizedBox(height: 20),
            FutureBuilder<List<Map<String, Object?>>>(
              future: service.pendingOperations(),
              builder: (context, snapshot) => Column(
                children: [
                  for (final op in snapshot.data ?? [])
                    Card(
                      child: ListTile(
                        leading: Icon(
                          op['state'] == 'conflict'
                              ? Icons.error_outline
                              : op['state'] == 'accepted'
                              ? Icons.check_circle_outline
                              : Icons.schedule,
                        ),
                        title: Text('${op['method']} ${op['path']}'),
                        onTap: op['state'] == 'conflict'
                            ? () => Navigator.of(
                                context,
                              ).push(MaterialPageRoute(builder: (_) => ConflictReviewScreen(operation: op)))
                            : null,
                        subtitle: Text(
                          '${op['error'] ?? (op['state'] == 'accepted' ? 'Uploaded; refreshing saved data' : 'Safely saved on this device')}',
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class ConflictReviewScreen extends StatefulWidget {
  final Map<String, Object?> operation;
  const ConflictReviewScreen({super.key, required this.operation});
  @override
  State<ConflictReviewScreen> createState() => _ConflictReviewScreenState();
}

class _ConflictReviewScreenState extends State<ConflictReviewScreen> {
  late final Future<Map<String, dynamic>> review = OfflineService.instance.reviewConflict(widget.operation);
  bool saving = false;
  String label(String value) =>
      value.replaceAllMapped(RegExp(r'([a-z])([A-Z])'), (m) => '${m[1]} ${m[2]}').replaceAll('_', ' ');
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Review saved change')),
    body: FutureBuilder<Map<String, dynamic>>(
      future: review,
      builder: (context, snapshot) {
        if (snapshot.hasError) return Padding(padding: const EdgeInsets.all(24), child: Text('${snapshot.error}'));
        if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
        final latest = snapshot.data!['data'] as Map;
        final fields = jsonDecode(widget.operation['fields'] as String) as Map;
        final keys = fields.keys
            .where((key) => !'$key'.startsWith('_') && !'$key'.contains('password') && latest.containsKey(key))
            .toList();
        return ListView(
          padding: const EdgeInsets.all(20),
          children: [
            const Text(
              'Another change was saved on the server. Check the values below before applying your saved change.',
            ),
            const SizedBox(height: 16),
            for (final key in keys)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(label('$key'), style: Theme.of(context).textTheme.titleSmall),
                      Text('On server: ${latest[key] ?? '—'}'),
                      Text('Saved on phone: ${fields[key] ?? '—'}'),
                    ],
                  ),
                ),
              ),
            if ('${widget.operation['method']}'.toUpperCase() == 'DELETE')
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('Your saved action will delete this record.'),
              ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: saving
                  ? null
                  : () async {
                      setState(() => saving = true);
                      try {
                        await OfflineService.instance.applyReviewedConflict(widget.operation, snapshot.data!);
                        if (context.mounted) Navigator.pop(context);
                      } catch (error) {
                        if (context.mounted)
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$error')));
                      } finally {
                        if (mounted) setState(() => saving = false);
                      }
                    },
              child: Text(saving ? 'Saving…' : 'Apply my change to this version'),
            ),
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Keep saved work and decide later')),
          ],
        );
      },
    ),
  );
}
