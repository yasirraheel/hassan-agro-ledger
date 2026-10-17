import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:mobile_pos/offline/offline_media.dart';
import 'package:image/image.dart' as img;
import '_base_widget.dart';

class ThermerImage extends ThermerWidget {
  final ui.Image image;
  final double? width;
  final double? height;

  const ThermerImage({
    required this.image,
    this.width,
    this.height,
  });

  static Future<ui.Image> _convertImageToUiImage(img.Image image) async {
    final pngBytes = img.encodePng(image);
    final codec = await ui.instantiateImageCodec(pngBytes);
    final frame = await codec.getNextFrame();
    return frame.image;
  }

  static Future<ThermerImage> network(
    String url, {
    double? width,
    double? height,
  }) async {
    final image = img.decodeImage(await OfflineMedia.bytes(url));
    if (image == null) {
      throw Exception('Failed to decode image from $url');
    }
    final uiImage = await _convertImageToUiImage(image);
    return ThermerImage(
      image: uiImage,
      width: width,
      height: height,
    );
  }

  static Future<ThermerImage> memory(
    Uint8List bytes, {
    double? width,
    double? height,
  }) async {
    final image = img.decodeImage(bytes);
    if (image == null) {
      throw Exception('Failed to decode image from bytes');
    }
    final uiImage = await _convertImageToUiImage(image);
    return ThermerImage(
      image: uiImage,
      width: width,
      height: height,
    );
  }
}

