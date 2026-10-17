import 'package:mobile_pos/offline/offline_revision_provider.dart';
//_____________________________________________Tax_provider_____________________
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/vat_model.dart';
import '../repo/tax_repo.dart';

TaxRepo taxRepo = TaxRepo();
final taxProvider = FutureProvider<List<VatModel>>((ref) { ref.watch(offlineRevisionProvider); return taxRepo.fetchAllTaxes(taxType: ''); });

//_____________________________________________Group_Tax_provider_____________________
final singleTaxProvider = FutureProvider.autoDispose<List<VatModel>>((ref) { ref.watch(offlineRevisionProvider); return taxRepo.fetchAllTaxes(taxType: 'single'); });
