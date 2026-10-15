extension Numx on num {
  num formatDecimal({int decimalDigits = 2}) {
    final _isInt = toInt() == this;
    return _isInt ? toInt() : num.parse(toStringAsFixed(decimalDigits));
  }
}
