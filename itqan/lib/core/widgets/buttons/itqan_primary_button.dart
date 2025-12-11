import 'package:flutter/material.dart';
import '../../theme/colors.dart';
import '../../theme/spacing.dart';
import '../../theme/typography.dart';

/// Primary button with gradient background and shimmer effect
class ItqanPrimaryButton extends StatefulWidget {
  final String text;
  final VoidCallback? onPressed;
  final bool isLoading;
  final bool isFullWidth;
  final IconData? icon;
  final LinearGradient? gradient;
  final double? height;

  const ItqanPrimaryButton({
    super.key,
    required this.text,
    this.onPressed,
    this.isLoading = false,
    this.isFullWidth = true,
    this.icon,
    this.gradient,
    this.height,
  });

  @override
  State<ItqanPrimaryButton> createState() => _ItqanPrimaryButtonState();
}

class _ItqanPrimaryButtonState extends State<ItqanPrimaryButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _shimmerController;
  late Animation<double> _shimmerAnimation;

  @override
  void initState() {
    super.initState();
    _shimmerController = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _shimmerAnimation = Tween<double>(begin: -1.0, end: 2.0).animate(
      CurvedAnimation(parent: _shimmerController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _shimmerController.dispose();
    super.dispose();
  }

  void _onHoverStart() {
    _shimmerController.repeat();
  }

  void _onHoverEnd() {
    _shimmerController.stop();
    _shimmerController.reset();
  }

  @override
  Widget build(BuildContext context) {
    final isDisabled = widget.onPressed == null || widget.isLoading;
    final gradient = widget.gradient ?? AppColors.primaryGradient;

    return MouseRegion(
      onEnter: (_) => _onHoverStart(),
      onExit: (_) => _onHoverEnd(),
      child: AnimatedContainer(
        duration: AppSpacing.durationQuick,
        width: widget.isFullWidth ? double.infinity : null,
        height: widget.height ?? 52,
        decoration: BoxDecoration(
          gradient: isDisabled ? null : gradient,
          color: isDisabled ? AppColors.secondary200 : null,
          borderRadius: AppSpacing.borderRadiusMd,
          boxShadow: isDisabled ? null : AppSpacing.shadowMd,
        ),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: isDisabled ? null : widget.onPressed,
            borderRadius: AppSpacing.borderRadiusMd,
            child: AnimatedBuilder(
              animation: _shimmerAnimation,
              builder: (context, child) {
                return Stack(
                  children: [
                    // Button content
                    Center(
                      child: widget.isLoading
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  AppColors.textLight,
                                ),
                              ),
                            )
                          : Row(
                              mainAxisSize: MainAxisSize.min,
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                if (widget.icon != null) ...[
                                  Icon(
                                    widget.icon,
                                    color: isDisabled
                                        ? AppColors.textMuted
                                        : AppColors.textLight,
                                    size: 20,
                                  ),
                                  const SizedBox(width: AppSpacing.sm),
                                ],
                                Text(
                                  widget.text,
                                  style: AppTypography.buttonMedium.copyWith(
                                    color: isDisabled
                                        ? AppColors.textMuted
                                        : AppColors.textLight,
                                  ),
                                ),
                              ],
                            ),
                    ),
                    // Shimmer effect
                    if (!isDisabled)
                      Positioned.fill(
                        child: ClipRRect(
                          borderRadius: AppSpacing.borderRadiusMd,
                          child: Transform.translate(
                            offset: Offset(
                              _shimmerAnimation.value *
                                  MediaQuery.of(context).size.width,
                              0,
                            ),
                            child: Container(
                              width: 100,
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [
                                    Colors.transparent,
                                    Colors.white.withOpacity(0.2),
                                    Colors.transparent,
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
