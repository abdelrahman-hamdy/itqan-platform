import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../theme/colors.dart';
import '../../theme/typography.dart';

/// User avatar with fallback to initials
class ItqanAvatar extends StatelessWidget {
  final String? imageUrl;
  final String name;
  final double size;
  final Color? backgroundColor;
  final Color? textColor;
  final bool showBorder;

  const ItqanAvatar({
    super.key,
    this.imageUrl,
    required this.name,
    this.size = 40,
    this.backgroundColor,
    this.textColor,
    this.showBorder = false,
  });

  @override
  Widget build(BuildContext context) {
    final initials = _getInitials(name);
    final bgColor = backgroundColor ?? _getColorFromName(name);

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: bgColor,
        border: showBorder
            ? Border.all(color: AppColors.surface, width: 2)
            : null,
        boxShadow: showBorder
            ? [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ]
            : null,
      ),
      child: imageUrl != null && imageUrl!.isNotEmpty
          ? ClipOval(
              child: CachedNetworkImage(
                imageUrl: imageUrl!,
                width: size,
                height: size,
                fit: BoxFit.cover,
                placeholder: (context, url) => _buildInitials(initials, bgColor),
                errorWidget: (context, url, error) =>
                    _buildInitials(initials, bgColor),
              ),
            )
          : _buildInitials(initials, bgColor),
    );
  }

  Widget _buildInitials(String initials, Color bgColor) {
    return Center(
      child: Text(
        initials,
        style: TextStyle(
          color: textColor ?? AppColors.textLight,
          fontSize: size * 0.4,
          fontWeight: FontWeight.w600,
          fontFamily: AppTypography.primaryFont,
        ),
      ),
    );
  }

  String _getInitials(String name) {
    final parts = name.trim().split(' ');
    if (parts.isEmpty) return '?';
    if (parts.length == 1) {
      return parts[0].isNotEmpty ? parts[0][0].toUpperCase() : '?';
    }
    return '${parts[0][0]}${parts[parts.length - 1][0]}'.toUpperCase();
  }

  Color _getColorFromName(String name) {
    final colors = [
      AppColors.primary,
      AppColors.accent,
      AppColors.scheduled,
      AppColors.gradientVioletStart,
      AppColors.warning,
    ];
    final index = name.hashCode.abs() % colors.length;
    return colors[index];
  }
}

/// Avatar with online status indicator
class ItqanAvatarWithStatus extends StatelessWidget {
  final String? imageUrl;
  final String name;
  final double size;
  final bool isOnline;

  const ItqanAvatarWithStatus({
    super.key,
    this.imageUrl,
    required this.name,
    this.size = 40,
    this.isOnline = false,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        ItqanAvatar(
          imageUrl: imageUrl,
          name: name,
          size: size,
        ),
        Positioned(
          right: 0,
          bottom: 0,
          child: Container(
            width: size * 0.3,
            height: size * 0.3,
            decoration: BoxDecoration(
              color: isOnline ? AppColors.ongoing : AppColors.textTertiary,
              shape: BoxShape.circle,
              border: Border.all(color: AppColors.surface, width: 2),
            ),
          ),
        ),
      ],
    );
  }
}
