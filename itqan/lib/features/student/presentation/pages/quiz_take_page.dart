import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/theme/colors.dart';
import '../../../../core/theme/spacing.dart';
import '../../../../core/theme/typography.dart';
import '../../../../core/widgets/buttons/itqan_primary_button.dart';
import '../../../../mock/mock_data.dart';

class StudentQuizTakePage extends StatefulWidget {
  final String quizId;

  const StudentQuizTakePage({super.key, required this.quizId});

  @override
  State<StudentQuizTakePage> createState() => _StudentQuizTakePageState();
}

class _StudentQuizTakePageState extends State<StudentQuizTakePage> {
  int _currentQuestion = 0;
  final Map<int, int> _answers = {};
  late int _remainingSeconds;
  Timer? _timer;

  // Mock questions
  final List<_MockQuestion> _questions = [
    _MockQuestion(
      question: 'ما هو حكم النون الساكنة إذا جاء بعدها حرف الباء؟',
      options: ['إظهار', 'إدغام', 'إقلاب', 'إخفاء'],
      correctIndex: 2,
    ),
    _MockQuestion(
      question: 'كم عدد حروف الإظهار الحلقي؟',
      options: ['4 حروف', '5 حروف', '6 حروف', '7 حروف'],
      correctIndex: 2,
    ),
    _MockQuestion(
      question: 'ما هو الإدغام بغنة؟',
      options: [
        'إدخال حرف في حرف مع بقاء الغنة',
        'إدخال حرف في حرف بدون غنة',
        'إظهار الحرف مع الغنة',
        'إخفاء الحرف مع الغنة',
      ],
      correctIndex: 0,
    ),
    _MockQuestion(
      question: 'ما هي مدة الغنة في الإدغام بغنة؟',
      options: ['حركة واحدة', 'حركتان', 'ثلاث حركات', 'أربع حركات'],
      correctIndex: 1,
    ),
    _MockQuestion(
      question: 'أي من الحروف التالية من حروف الإخفاء؟',
      options: ['الهمزة', 'الكاف', 'الهاء', 'العين'],
      correctIndex: 1,
    ),
  ];

  MockQuiz? get quiz {
    try {
      return MockDataProvider.quizzes.firstWhere((q) => q.id == widget.quizId);
    } catch (_) {
      return null;
    }
  }

  @override
  void initState() {
    super.initState();
    _remainingSeconds = (quiz?.timeLimitMinutes ?? 20) * 60;
    _startTimer();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds > 0) {
        setState(() => _remainingSeconds--);
      } else {
        _submitQuiz();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _selectAnswer(int answerIndex) {
    setState(() {
      _answers[_currentQuestion] = answerIndex;
    });
  }

  void _nextQuestion() {
    if (_currentQuestion < _questions.length - 1) {
      setState(() => _currentQuestion++);
    }
  }

  void _previousQuestion() {
    if (_currentQuestion > 0) {
      setState(() => _currentQuestion--);
    }
  }

  void _submitQuiz() {
    _timer?.cancel();
    context.go('/student/more/quizzes/${widget.quizId}/result');
  }

  String get _formattedTime {
    final minutes = _remainingSeconds ~/ 60;
    final seconds = _remainingSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final q = quiz;
    if (q == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('الاختبار')),
        body: const Center(child: Text('الاختبار غير موجود')),
      );
    }

    final currentQ = _questions[_currentQuestion];
    final isLastQuestion = _currentQuestion == _questions.length - 1;
    final isAnswered = _answers.containsKey(_currentQuestion);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => _showExitDialog(context),
        ),
        title: Text(q.title),
        actions: [
          Container(
            margin: const EdgeInsets.only(left: AppSpacing.md),
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
            decoration: BoxDecoration(
              color: _remainingSeconds < 60 ? AppColors.error.withValues(alpha: 0.1) : AppColors.primary100,
              borderRadius: AppSpacing.borderRadiusFull,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.timer,
                  size: 18,
                  color: _remainingSeconds < 60 ? AppColors.error : AppColors.primary,
                ),
                const SizedBox(width: 4),
                Text(
                  _formattedTime,
                  style: AppTypography.labelMedium.copyWith(
                    color: _remainingSeconds < 60 ? AppColors.error : AppColors.primary,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // Progress Bar
          LinearProgressIndicator(
            value: (_currentQuestion + 1) / _questions.length,
            backgroundColor: AppColors.border,
            valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
          ),

          Expanded(
            child: SingleChildScrollView(
              padding: AppSpacing.paddingScreenAll,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Question Number
                  Text(
                    'السؤال ${_currentQuestion + 1} من ${_questions.length}',
                    style: AppTypography.caption.copyWith(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: AppSpacing.md),

                  // Question
                  Container(
                    width: double.infinity,
                    padding: AppSpacing.paddingCard,
                    decoration: BoxDecoration(
                      color: AppColors.primary100,
                      borderRadius: AppSpacing.borderRadiusMd,
                    ),
                    child: Text(currentQ.question, style: AppTypography.titleMedium),
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  // Options
                  ...List.generate(currentQ.options.length, (index) {
                    final isSelected = _answers[_currentQuestion] == index;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: AppSpacing.md),
                      child: GestureDetector(
                        onTap: () => _selectAnswer(index),
                        child: Container(
                          width: double.infinity,
                          padding: AppSpacing.paddingCard,
                          decoration: BoxDecoration(
                            color: isSelected ? AppColors.primary.withValues(alpha: 0.1) : AppColors.surface,
                            borderRadius: AppSpacing.borderRadiusMd,
                            border: Border.all(
                              color: isSelected ? AppColors.primary : AppColors.border,
                              width: isSelected ? 2 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  color: isSelected ? AppColors.primary : AppColors.surfaceVariant,
                                  shape: BoxShape.circle,
                                ),
                                child: Center(
                                  child: Text(
                                    String.fromCharCode(65 + index), // A, B, C, D
                                    style: AppTypography.labelMedium.copyWith(
                                      color: isSelected ? Colors.white : AppColors.textSecondary,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: AppSpacing.md),
                              Expanded(
                                child: Text(
                                  currentQ.options[index],
                                  style: AppTypography.bodyMedium.copyWith(
                                    color: isSelected ? AppColors.primary : AppColors.textPrimary,
                                  ),
                                ),
                              ),
                              if (isSelected)
                                const Icon(Icons.check_circle, color: AppColors.primary),
                            ],
                          ),
                        ),
                      ),
                    );
                  }),
                ],
              ),
            ),
          ),

          // Navigation Buttons
          Container(
            padding: AppSpacing.paddingScreenAll,
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(top: BorderSide(color: AppColors.border)),
            ),
            child: Row(
              children: [
                if (_currentQuestion > 0)
                  Expanded(
                    child: OutlinedButton(
                      onPressed: _previousQuestion,
                      child: const Text('السابق'),
                    ),
                  ),
                if (_currentQuestion > 0) const SizedBox(width: AppSpacing.md),
                Expanded(
                  flex: _currentQuestion > 0 ? 1 : 2,
                  child: isLastQuestion
                      ? ItqanPrimaryButton(
                          text: 'إنهاء الاختبار',
                          onPressed: isAnswered ? _submitQuiz : null,
                        )
                      : ItqanPrimaryButton(
                          text: 'التالي',
                          onPressed: isAnswered ? _nextQuestion : null,
                        ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showExitDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('إنهاء الاختبار؟'),
        content: const Text('هل أنت متأكد من رغبتك في إنهاء الاختبار؟ سيتم فقدان تقدمك.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('متابعة'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              context.pop();
            },
            child: Text('إنهاء', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }
}

class _MockQuestion {
  final String question;
  final List<String> options;
  final int correctIndex;

  _MockQuestion({
    required this.question,
    required this.options,
    required this.correctIndex,
  });
}
