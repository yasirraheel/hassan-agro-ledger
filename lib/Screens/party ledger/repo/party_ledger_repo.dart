import 'dart:convert';

import 'package:http/http.dart' as http;

import '../../../Const/api_config.dart';
import '../../../http_client/customer_http_client_get.dart';
import '../model/party_ledger_model.dart';

// class PartyLedgerRepo {
//   // ... existing code ...
//
//   Future<PartyLedgerResponse> getPartyLedger({
//     required String partyId,
//     required int page,
//     String? duration, // e.g., 'today', 'this_month', etc.
//   }) async {
//     // Construct URL with pagination AND duration filter
//     String url = '${APIConfig.url}/party-ledger/$partyId?page=$page';
//
//     // Append filter if it exists
//     if (duration != null && duration != 'All') {
//       url += '&duration=${duration.toLowerCase().replaceAll(' ', '_')}';
//       // Example: "This Month" becomes "&duration=this_month"
//     }
//
//     final uri = Uri.parse(url);
//     CustomHttpClientGet clientGet = CustomHttpClientGet(client: http.Client());
//     final response = await clientGet.get(url: uri);
//
//     if (response.statusCode == 200) {
//       final parsedData = jsonDecode(response.body) as Map<String, dynamic>;
//
//       final paginationData = parsedData['data']['data'] as List<dynamic>;
//       final metaData = parsedData['data'];
//
//       List<PartyLedgerModel> ledgerList = paginationData.map((item) => PartyLedgerModel.fromJson(item)).toList();
//
//       return PartyLedgerResponse(
//         data: ledgerList,
//         lastPage: metaData['last_page'] ?? 1,
//         currentPage: metaData['current_page'] ?? 1,
//       );
//     } else {
//       throw Exception('Failed to fetch ledger');
//     }
//   }
//
// // ... existing code ...
// }


// class PartyLedgerRepo {
//   Future<PartyLedgerResponse> getPartyLedger({
//     required String partyId,
//     required int page,
//     String? duration, // same format as dashboard
//   }) async {
//     String url = '${APIConfig.url}/party-ledger/$partyId?page=$page';
//
//     if (duration != null && duration != 'All' && duration.isNotEmpty) {
//       if (duration.startsWith('custom_date&')) {
//         final params = Uri.splitQueryString(duration.replaceFirst('custom_date&', ''));
//         final fromDate = params['from_date'];
//         final toDate = params['to_date'];
//
//         // append exact query string as dashboard does
//         url += '&duration=custom_date&from_date=$fromDate&to_date=$toDate';
//       } else {
//         // simple durations like 'today', 'this_month', 'this_week', etc.
//         url += '&duration=$duration';
//       }
//     }
//
//     final uri = Uri.parse(url);
//
//     print('-------url----${uri}-----------------');
//     final clientGet = CustomHttpClientGet(client: http.Client());
//     final response = await clientGet.get(url: uri);
//
//     print('--------status code----${response.statusCode}-------------');
//
//     if (response.statusCode == 200) {
//       final parsed = jsonDecode(response.body) as Map<String, dynamic>;
//       final paginationList = parsed['data']['data'] as List<dynamic>;
//       final meta = parsed['data'];
//
//       final ledgerList = paginationList.map((e) => PartyLedgerModel.fromJson(e as Map<String, dynamic>)).toList();
//
//       return PartyLedgerResponse(
//         data: ledgerList,
//         lastPage: meta['last_page'] ?? 1,
//         currentPage: meta['current_page'] ?? 1,
//       );
//     } else {
//       throw Exception('Failed to fetch ledger ${response.statusCode}');
//     }
//   }
// }

class PartyLedgerRepo {
  Future<PartyLedgerResponse> getPartyLedger({
    required String partyId,
    required int page,
    String? duration,
  }) async {
    try {
      String url = '${APIConfig.url}/party-ledger/$partyId?page=$page';

      if (duration != null && duration != 'All' && duration.isNotEmpty) {
        if (duration.startsWith('custom_date&')) {
          final params = Uri.splitQueryString(duration.replaceFirst('custom_date&', ''));
          final fromDate = params['from_date'];
          final toDate = params['to_date'];

          url += '&duration=custom_date&from_date=$fromDate&to_date=$toDate';
        } else {
          url += '&duration=$duration';
        }
      }

      final uri = Uri.parse(url);
      print('-------url----$uri-----------------');

      final clientGet = CustomHttpClientGet(client: http.Client());
      final response = await clientGet.get(url: uri);

      print('--------status code----${response.statusCode}-------------');
      print('--------response body----${response.body}-------------');

      if (response.statusCode == 200) {
        final parsed = jsonDecode(response.body) as Map<String, dynamic>;

        final dataList = parsed['data'] as List<dynamic>;

        final ledgerList = dataList.map((e) => PartyLedgerModel.fromJson(e as Map<String, dynamic>)).toList();

        return PartyLedgerResponse(
          data: ledgerList,
          lastPage: parsed['last_page'] ?? 1,
          currentPage: parsed['current_page'] ?? page,
        );
      } else {
        final _body = jsonDecode(response.body);
        throw Exception(_body?['message']?.toString() ?? 'HTTP ${response.statusCode}');
      }
    } catch (e) {
      rethrow;
    }
  }
}
