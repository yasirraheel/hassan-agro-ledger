import 'package:mobile_pos/offline/offline_media.dart';
import 'package:image/image.dart' as img;

Future<img.Image?> getNetworkImage(
  String? url, {
  int width = 250,
  int height = 250,
}) async {
  if (url == null) return null;

  try {
    final _image = img.decodeImage(await OfflineMedia.bytes(url));
    if (_image == null) return null;
    return _image;
    return img.copyResize(
      _image,
      width: width,
      height: height,
      interpolation: img.Interpolation.average,
    );
    // final img.Image grayscaleImage = img.grayscale(resizedImage);
  } catch (e) {
    return null;
  }
}
