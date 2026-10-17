import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_pos/offline/remember_filters.dart';

void main() {
  test('remembered rolling and calendar ranges are recalculated from the current day', () {
    final now = DateTime(2026, 10, 17, 15, 20);
    expect(FilterMemory.range('last_thirty_days', now: now).$1, DateTime(2026, 9, 18));
    expect(FilterMemory.range('last_thirty_days', now: now).$2, DateTime(2026, 10, 17));
    expect(FilterMemory.range('current_month', now: now).$1, DateTime(2026, 10, 1));
    expect(FilterMemory.range('current_month', now: now).$2, DateTime(2026, 10, 31));
    expect(FilterMemory.range('all', now: now), (null, null));
  });
}
