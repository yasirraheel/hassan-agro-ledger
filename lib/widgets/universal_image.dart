import 'dart:io';
import 'package:flutter/material.dart';
import '../offline/offline_media.dart';

class UniversalImage extends StatelessWidget {
  final String? imagePath;
  final double? height;
  final double? width;
  final BoxFit fit;
  final Widget? placeholder;

  const UniversalImage({
    super.key,
    required this.imagePath,
    this.height,
    this.width,
    this.fit = BoxFit.contain,
    this.placeholder,
  });

  @override
  Widget build(BuildContext context) {
    if (imagePath == null || imagePath!.isEmpty) {
      return _placeholder();
    }

    /// Network Image
    if (imagePath!.startsWith('http')) {
      return Image(
        image: offlineImageProvider(imagePath!),
        height: height,
        width: width,
        fit: fit,
        errorBuilder: (_, _, _) => _placeholder(),
      );
    }

    /// Local File Image
    if (imagePath!.startsWith('/')) {
      return Image.file(
        File(imagePath!),
        height: height,
        width: width,
        fit: fit,
        errorBuilder: (_, _, _) => _placeholder(),
      );
    }

    /// Asset Image
    return Image.asset(
      imagePath!,
      height: height,
      width: width,
      fit: fit,
      errorBuilder: (_, _, _) => _placeholder(),
    );
  }

  Widget _placeholder() {
    return placeholder ??
        SizedBox(
          height: height,
          width: width,
          child: const Icon(Icons.image_not_supported, size: 40),
        );
  }
}
