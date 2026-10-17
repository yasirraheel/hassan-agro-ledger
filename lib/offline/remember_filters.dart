import 'dart:async';
import 'dart:convert';
import 'package:flutter/widgets.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'offline_service.dart';

class FilterMemory {
  static String key(String account,String screen) => 'filters.v1:$account:$screen';
  static Future<Map<String,dynamic>> read(String account,String screen) async {
    final value = (await SharedPreferences.getInstance()).getString(key(account,screen));
    if(value==null) return {};
    try { return Map<String,dynamic>.from(jsonDecode(value)); } catch(_) { return {}; }
  }
  static Future<void> write(String account,String screen,Map<String,dynamic> values) async {
    await (await SharedPreferences.getInstance()).setString(key(account,screen),jsonEncode(values));
  }
  static (DateTime?,DateTime?) range(String duration,{DateTime? from,DateTime? to,DateTime? now}) {
    final date=now??DateTime.now();
    final today=DateTime(date.year,date.month,date.day);
    return switch(duration) {
      'today'||'Today'=>(today,today),
      'yesterday'||'Yesterday'=>(DateTime(date.year,date.month,date.day-1),DateTime(date.year,date.month,date.day-1)),
      'last_seven_days'||'Last 7 Days'=>(DateTime(date.year,date.month,date.day-6),today),
      'last_thirty_days'||'Last 30 Days'=>(DateTime(date.year,date.month,date.day-29),today),
      'current_month'||'Current Month'=>(DateTime(date.year,date.month),DateTime(date.year,date.month+1,0)),
      'last_month'||'Last Month'=>(DateTime(date.year,date.month-1),DateTime(date.year,date.month,0)),
      'current_year'||'Current Year'=>(DateTime(date.year),DateTime(date.year,12,31)),
      'all'||'All'=>(null,null),
      _=>(from,to),
    };
  }
}

/// Stores only explicitly selected filter fields, separately for each account and screen.
mixin RememberFilters<T extends StatefulWidget> on State<T> {
  String get rememberedFilterKey;
  Map<String,dynamic> get rememberedFilters;
  void restoreRememberedFilters(Map<String,dynamic> values);
  void didRestoreRememberedFilters() {}
  late final String _filterAccount;
  bool _filtersLoaded=false, _restoringFilters=false;
  String? _lastFilters;
  Future<void> _filterWrites=Future.value();

  @override
  void initState() {
    super.initState();
    _filterAccount=OfflineService.instance.scope??'signed-out';
    unawaited(_restoreFilters());
  }
  Future<void> _restoreFilters() async {
    final values=await FilterMemory.read(_filterAccount,rememberedFilterKey);
    if(!mounted) return;
    _restoringFilters=true;
    try {
      if(values.isNotEmpty) setState(() => restoreRememberedFilters(values));
      _lastFilters=jsonEncode(rememberedFilters);
      _filtersLoaded=true;
      if(values.isNotEmpty) didRestoreRememberedFilters();
    } finally { _restoringFilters=false; }
  }
  @override
  void setState(VoidCallback fn) {
    super.setState(fn);
    if(!_filtersLoaded || _restoringFilters) return;
    final encoded=jsonEncode(rememberedFilters);
    if(encoded==_lastFilters) return;
    _lastFilters=encoded;
    final values=Map<String,dynamic>.from(jsonDecode(encoded));
    _filterWrites=_filterWrites.then((_) => FilterMemory.write(_filterAccount,rememberedFilterKey,values));
  }
}
