import 'dart:math' as math;
import 'package:flutter/material.dart';
import '../../theme/colors.dart';
import '../../theme/typography.dart';

/// Circular progress indicator with percentage
class CircularProgressIndicatorWithLabel extends StatelessWidget {
  final double progress; // 0.0 to 1.0
  final double size;
  final double strokeWidth;
  final Color? progressColor;
  final Color? backgroundColor;
  final Widget? center;
  final bool showPercentage;

  const CircularProgressIndicatorWithLabel({
    super.key,
    required this.progress,
    this.size = 80,
    this.strokeWidth = 8,
    this.progressColor,
    this.backgroundColor,
    this.center,
    this.showPercentage = true,
  });

  @override
  Widget build(BuildContext context) {
    final clampedProgress = progress.clamp(0.0, 1.0);
    final percentage = (clampedProgress * 100).round();

    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Background circle
          CustomPaint(
            size: Size(size, size),
            painter: _CircleProgressPainter(
              progress: 1.0,
              strokeWidth: strokeWidth,
              color: backgroundColor ?? AppColors.secondary100,
            ),
          ),
          // Progress arc
          TweenAnimationBuilder<double>(
            tween: Tween(begin: 0, end: clampedProgress),
            duration: const Duration(milliseconds: 800),
            curve: Curves.easeOutCubic,
            builder: (context, value, child) {
              return CustomPaint(
                size: Size(size, size),
                painter: _CircleProgressPainter(
                  progress: value,
                  strokeWidth: strokeWidth,
                  color: progressColor ?? AppColors.primary,
                ),
              );
            },
          ),
          // Center content
          if (center != null)
            center!
          else if (showPercentage)
            Text(
              '$percentage%',
              style: AppTypography.titleMedium.copyWith(
                fontWeight: FontWeight.w700,
                color: progressColor ?? AppColors.primary,
              ),
            ),
        ],
      ),
    );
  }
}

class _CircleProgressPainter extends CustomPainter {
  final double progress;
  final double strokeWidth;
  final Color color;

  _CircleProgressPainter({
    required this.progress,
    required this.strokeWidth,
    required this.color,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - strokeWidth) / 2;

    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    // Start from top (-90 degrees or -pi/2)
    const startAngle = -math.pi / 2;
    final sweepAngle = 2 * math.pi * progress;

    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      startAngle,
      sweepAngle,
      false,
      paint,
    );
  }

  @override
  bool shouldRepaint(covariant _CircleProgressPainter oldDelegate) {
    return oldDelegate.progress != progress ||
        oldDelegate.color != color ||
        oldDelegate.strokeWidth != strokeWidth;
  }
}

/// Gradient circular progress
class GradientCircularProgress extends StatelessWidget {
  final double progress;
  final double size;
  final double strokeWidth;
  final LinearGradient? gradient;
  final bool showPercentage;

  const GradientCircularProgress({
    super.key,
    required this.progress,
    this.size = 80,
    this.strokeWidth = 8,
    this.gradient,
    this.showPercentage = true,
  });

  @override
  Widget build(BuildContext context) {
    final clampedProgress = progress.clamp(0.0, 1.0);
    final percentage = (clampedProgress * 100).round();

    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Background
          CustomPaint(
            size: Size(size, size),
            painter: _CircleProgressPainter(
              progress: 1.0,
              strokeWidth: strokeWidth,
              color: AppColors.secondary100,
            ),
          ),
          // Gradient progress
          ShaderMask(
            shaderCallback: (rect) {
              return (gradient ?? AppColors.primaryGradient).createShader(rect);
            },
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: clampedProgress),
              duration: const Duration(milliseconds: 800),
              curve: Curves.easeOutCubic,
              builder: (context, value, child) {
                return CustomPaint(
                  size: Size(size, size),
                  painter: _CircleProgressPainter(
                    progress: value,
                    strokeWidth: strokeWidth,
                    color: Colors.white,
                  ),
                );
              },
            ),
          ),
          // Percentage
          if (showPercentage)
            Text(
              '$percentage%',
              style: AppTypography.titleMedium.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
        ],
      ),
    );
  }
}

/// Small circular progress for inline use
class SmallCircularProgress extends StatelessWidget {
  final double progress;
  final double size;
  final Color? color;

  const SmallCircularProgress({
    super.key,
    required this.progress,
    this.size = 24,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return CircularProgressIndicatorWithLabel(
      progress: progress,
      size: size,
      strokeWidth: 3,
      progressColor: color,
      showPercentage: false,
      center: Text(
        '${(progress * 100).round()}',
        style: TextStyle(
          fontSize: size * 0.35,
          fontWeight: FontWeight.w600,
          color: color ?? AppColors.primary,
        ),
      ),
    );
  }
}
