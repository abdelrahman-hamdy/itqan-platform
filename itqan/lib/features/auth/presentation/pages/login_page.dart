import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/router/routes.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../core/widgets/buttons/itqan_secondary_button.dart';

/// Login page with role-based routing
class LoginPage extends StatefulWidget {
  final String role;

  const LoginPage({super.key, required this.role});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _handleLogin() {
    setState(() => _isLoading = true);

    // Simulate login delay then bypass to dashboard
    Future.delayed(const Duration(milliseconds: 800), () {
      if (!mounted) return;
      setState(() => _isLoading = false);

      // Route based on role
      switch (widget.role) {
        case 'student':
          context.go(AppRoutes.studentHome);
          break;
        case 'teacher':
          context.go(AppRoutes.teacherHome);
          break;
        case 'parent':
          context.go(AppRoutes.parentHome);
          break;
        default:
          context.go(AppRoutes.studentHome);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () => context.pop(),
        ),
        title: Text(_getRoleTitle()),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: AppSpacing.paddingScreenAll,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: AppSpacing.xxl),

              // Logo
              Center(
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: AppColors.primary100,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(
                    Icons.menu_book,
                    size: 40,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.xl),

              // Title
              Text(
                'تسجيل الدخول',
                style: AppTypography.textTheme.headlineSmall,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(
                'أدخل بياناتك للوصول لحسابك',
                style: AppTypography.textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: AppSpacing.xxxl),

              // Email field
              Text(
                'البريد الإلكتروني',
                style: AppTypography.inputLabel,
              ),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  hintText: 'example@email.com',
                  prefixIcon: Icon(Icons.email_outlined),
                ),
              ),

              const SizedBox(height: AppSpacing.lg),

              // Password field
              Text(
                'كلمة المرور',
                style: AppTypography.inputLabel,
              ),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                decoration: InputDecoration(
                  hintText: '••••••••',
                  prefixIcon: const Icon(Icons.lock_outlined),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscurePassword
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                    ),
                    onPressed: () {
                      setState(() => _obscurePassword = !_obscurePassword);
                    },
                  ),
                ),
              ),

              const SizedBox(height: AppSpacing.md),

              // Forgot password
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton(
                  onPressed: () {
                    // TODO: Navigate to forgot password
                  },
                  child: Text(
                    'نسيت كلمة المرور؟',
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: AppSpacing.xl),

              // Login button
              ItqanPrimaryButton(
                text: 'تسجيل الدخول',
                onPressed: _handleLogin,
                isLoading: _isLoading,
              ),

              const SizedBox(height: AppSpacing.base),

              // Register link
              ItqanSecondaryButton(
                text: 'إنشاء حساب جديد',
                onPressed: () {
                  // TODO: Navigate to register
                },
              ),

              const SizedBox(height: AppSpacing.xxl),

              // Skip for testing
              Center(
                child: TextButton(
                  onPressed: _handleLogin,
                  child: Text(
                    'تخطي للاختبار',
                    style: AppTypography.caption.copyWith(
                      color: AppColors.textTertiary,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _getRoleTitle() {
    switch (widget.role) {
      case 'student':
        return 'دخول الطالب';
      case 'teacher':
        return 'دخول المعلم';
      case 'parent':
        return 'دخول ولي الأمر';
      default:
        return 'تسجيل الدخول';
    }
  }
}
