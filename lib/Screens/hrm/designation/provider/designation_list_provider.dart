import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/hrm/designation/Model/designation_list_model.dart';
import 'package:mobile_pos/Screens/hrm/designation/repo/designation_repo.dart';

final repo = DesignationRepo();
final designationListProvider = FutureProvider<DesignationListModel>((ref) => repo.fetchAllDesignation());
