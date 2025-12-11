/// Mock data for UI testing
/// All data is static and bypasses backend

class MockUser {
  final String id;
  final String firstName;
  final String lastName;
  final String email;
  final String? avatarUrl;
  final String role; // student, teacher, parent

  const MockUser({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.avatarUrl,
    required this.role,
  });

  String get fullName => '$firstName $lastName';
}

class MockTeacher {
  final String id;
  final String firstName;
  final String lastName;
  final String? avatarUrl;
  final String type; // quran, academic
  final double rating;
  final int reviewCount;
  final int experience;
  final List<String> subjects;
  final String? bio;
  final double sessionPrice;

  const MockTeacher({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.avatarUrl,
    required this.type,
    required this.rating,
    required this.reviewCount,
    required this.experience,
    required this.subjects,
    this.bio,
    required this.sessionPrice,
  });

  String get fullName => '$firstName $lastName';
}

class MockSession {
  final String id;
  final String type; // quran, academic, interactive
  final String format; // individual, group
  final String title;
  final MockTeacher teacher;
  final DateTime scheduledAt;
  final int durationMinutes;
  final String status; // scheduled, live, completed, cancelled
  final String? notes;

  const MockSession({
    required this.id,
    required this.type,
    required this.format,
    required this.title,
    required this.teacher,
    required this.scheduledAt,
    required this.durationMinutes,
    required this.status,
    this.notes,
  });
}

class MockCircle {
  final String id;
  final String name;
  final String type; // individual, group
  final MockTeacher teacher;
  final int studentCount;
  final double progress;
  final String status; // active, paused, completed
  final String schedule;

  const MockCircle({
    required this.id,
    required this.name,
    required this.type,
    required this.teacher,
    required this.studentCount,
    required this.progress,
    required this.status,
    required this.schedule,
  });
}

class MockCourse {
  final String id;
  final String title;
  final String description;
  final MockTeacher teacher;
  final String? thumbnailUrl;
  final int totalSessions;
  final int completedSessions;
  final double rating;
  final int enrolledCount;
  final double price;
  final String status; // upcoming, ongoing, completed

  const MockCourse({
    required this.id,
    required this.title,
    required this.description,
    required this.teacher,
    this.thumbnailUrl,
    required this.totalSessions,
    required this.completedSessions,
    required this.rating,
    required this.enrolledCount,
    required this.price,
    required this.status,
  });

  double get progress => totalSessions > 0 ? completedSessions / totalSessions : 0;
}

class MockHomework {
  final String id;
  final String title;
  final String type; // academic, quran, interactive
  final String? description;
  final DateTime dueDate;
  final String status; // pending, submitted, graded, overdue
  final double? grade;
  final String? feedback;

  const MockHomework({
    required this.id,
    required this.title,
    required this.type,
    this.description,
    required this.dueDate,
    required this.status,
    this.grade,
    this.feedback,
  });
}

class MockQuiz {
  final String id;
  final String title;
  final String? description;
  final int questionCount;
  final int timeLimitMinutes;
  final String status; // available, in_progress, completed
  final double? score;
  final double passingScore;

  const MockQuiz({
    required this.id,
    required this.title,
    this.description,
    required this.questionCount,
    required this.timeLimitMinutes,
    required this.status,
    this.score,
    required this.passingScore,
  });

  bool get isPassed => score != null && score! >= passingScore;
}

class MockSubscription {
  final String id;
  final String type; // quran_individual, quran_group, academic, course
  final String title;
  final MockTeacher? teacher;
  final String status; // active, paused, expired
  final int totalSessions;
  final int usedSessions;
  final DateTime? expiresAt;
  final bool autoRenewal;

  const MockSubscription({
    required this.id,
    required this.type,
    required this.title,
    this.teacher,
    required this.status,
    required this.totalSessions,
    required this.usedSessions,
    this.expiresAt,
    required this.autoRenewal,
  });

  int get remainingSessions => totalSessions - usedSessions;
  double get progress => totalSessions > 0 ? usedSessions / totalSessions : 0;
}

class MockPayment {
  final String id;
  final String code;
  final double amount;
  final DateTime date;
  final String status; // completed, pending, failed
  final String? subscriptionTitle;

  const MockPayment({
    required this.id,
    required this.code,
    required this.amount,
    required this.date,
    required this.status,
    this.subscriptionTitle,
  });
}

class MockCertificate {
  final String id;
  final String title;
  final String type; // quran, academic, course
  final DateTime issuedAt;
  final String certificateNumber;

  const MockCertificate({
    required this.id,
    required this.title,
    required this.type,
    required this.issuedAt,
    required this.certificateNumber,
  });
}

/// Mock Data Provider
class MockDataProvider {
  MockDataProvider._();

  // Current user (bypassed auth)
  static const MockUser currentStudent = MockUser(
    id: '1',
    firstName: 'أحمد',
    lastName: 'محمد',
    email: 'ahmed@example.com',
    role: 'student',
  );

  static const MockUser currentTeacher = MockUser(
    id: '2',
    firstName: 'خالد',
    lastName: 'العمري',
    email: 'khaled@example.com',
    role: 'teacher',
  );

  static const MockUser currentParent = MockUser(
    id: '3',
    firstName: 'عبدالله',
    lastName: 'السعيد',
    email: 'abdullah@example.com',
    role: 'parent',
  );

  // Teachers
  static const List<MockTeacher> teachers = [
    MockTeacher(
      id: '1',
      firstName: 'محمد',
      lastName: 'الشريف',
      type: 'quran',
      rating: 4.9,
      reviewCount: 156,
      experience: 10,
      subjects: ['تحفيظ القرآن', 'التجويد'],
      bio: 'معلم قرآن كريم حاصل على إجازة في القراءات العشر',
      sessionPrice: 50,
    ),
    MockTeacher(
      id: '2',
      firstName: 'فاطمة',
      lastName: 'الزهراني',
      type: 'quran',
      rating: 4.8,
      reviewCount: 98,
      experience: 7,
      subjects: ['تحفيظ القرآن', 'التجويد', 'القاعدة النورانية'],
      bio: 'معلمة قرآن متخصصة في تعليم الأطفال',
      sessionPrice: 45,
    ),
    MockTeacher(
      id: '3',
      firstName: 'عمر',
      lastName: 'الحربي',
      type: 'academic',
      rating: 4.7,
      reviewCount: 72,
      experience: 5,
      subjects: ['الرياضيات', 'الفيزياء'],
      bio: 'معلم رياضيات وفيزياء للمرحلة الثانوية',
      sessionPrice: 60,
    ),
    MockTeacher(
      id: '4',
      firstName: 'نورة',
      lastName: 'القحطاني',
      type: 'academic',
      rating: 4.9,
      reviewCount: 134,
      experience: 8,
      subjects: ['اللغة الإنجليزية'],
      bio: 'معلمة لغة إنجليزية حاصلة على ماجستير في اللغويات',
      sessionPrice: 55,
    ),
  ];

  // Sessions
  static List<MockSession> get sessions => [
    MockSession(
      id: '1',
      type: 'quran',
      format: 'individual',
      title: 'جلسة تحفيظ - سورة البقرة',
      teacher: teachers[0],
      scheduledAt: DateTime.now().add(const Duration(hours: 2)),
      durationMinutes: 45,
      status: 'scheduled',
    ),
    MockSession(
      id: '2',
      type: 'academic',
      format: 'individual',
      title: 'درس رياضيات - المعادلات',
      teacher: teachers[2],
      scheduledAt: DateTime.now().add(const Duration(days: 1)),
      durationMinutes: 60,
      status: 'scheduled',
    ),
    MockSession(
      id: '3',
      type: 'quran',
      format: 'group',
      title: 'حلقة التجويد',
      teacher: teachers[1],
      scheduledAt: DateTime.now().subtract(const Duration(hours: 3)),
      durationMinutes: 60,
      status: 'completed',
    ),
    MockSession(
      id: '4',
      type: 'interactive',
      format: 'group',
      title: 'دورة المحادثة الإنجليزية - الدرس 5',
      teacher: teachers[3],
      scheduledAt: DateTime.now(),
      durationMinutes: 90,
      status: 'live',
    ),
  ];

  // Circles
  static List<MockCircle> get circles => [
    MockCircle(
      id: '1',
      name: 'حلقة الإتقان للتحفيظ',
      type: 'group',
      teacher: teachers[0],
      studentCount: 8,
      progress: 0.45,
      status: 'active',
      schedule: 'السبت والإثنين والأربعاء - 4:00 م',
    ),
    MockCircle(
      id: '2',
      name: 'حلقة فردية - سورة يس',
      type: 'individual',
      teacher: teachers[1],
      studentCount: 1,
      progress: 0.72,
      status: 'active',
      schedule: 'الأحد والثلاثاء - 5:00 م',
    ),
  ];

  // Courses
  static List<MockCourse> get courses => [
    MockCourse(
      id: '1',
      title: 'دورة المحادثة الإنجليزية المتقدمة',
      description: 'تعلم المحادثة الإنجليزية بطلاقة من خلال 20 جلسة تفاعلية',
      teacher: teachers[3],
      totalSessions: 20,
      completedSessions: 5,
      rating: 4.8,
      enrolledCount: 25,
      price: 500,
      status: 'ongoing',
    ),
    MockCourse(
      id: '2',
      title: 'أساسيات الرياضيات للمرحلة الثانوية',
      description: 'مراجعة شاملة لأساسيات الرياضيات',
      teacher: teachers[2],
      totalSessions: 15,
      completedSessions: 0,
      rating: 4.7,
      enrolledCount: 18,
      price: 400,
      status: 'upcoming',
    ),
  ];

  // Homework
  static List<MockHomework> get homework => [
    MockHomework(
      id: '1',
      title: 'حفظ الصفحة 50-51 من سورة البقرة',
      type: 'quran',
      dueDate: DateTime.now().add(const Duration(days: 2)),
      status: 'pending',
    ),
    MockHomework(
      id: '2',
      title: 'حل تمارين الفصل الثالث',
      type: 'academic',
      description: 'حل جميع تمارين المعادلات من صفحة 45 إلى 50',
      dueDate: DateTime.now().add(const Duration(days: 1)),
      status: 'pending',
    ),
    MockHomework(
      id: '3',
      title: 'كتابة مقال بالإنجليزية',
      type: 'interactive',
      description: 'كتابة مقال 300 كلمة عن موضوع "My Future Plans"',
      dueDate: DateTime.now().subtract(const Duration(days: 1)),
      status: 'graded',
      grade: 8.5,
      feedback: 'عمل ممتاز! يحتاج لتحسين بسيط في القواعد.',
    ),
  ];

  // Quizzes
  static const List<MockQuiz> quizzes = [
    MockQuiz(
      id: '1',
      title: 'اختبار التجويد - أحكام النون الساكنة',
      questionCount: 15,
      timeLimitMinutes: 20,
      status: 'available',
      passingScore: 70,
    ),
    MockQuiz(
      id: '2',
      title: 'اختبار الرياضيات - المعادلات التربيعية',
      questionCount: 20,
      timeLimitMinutes: 30,
      status: 'completed',
      score: 85,
      passingScore: 60,
    ),
  ];

  // Subscriptions
  static List<MockSubscription> get subscriptions => [
    MockSubscription(
      id: '1',
      type: 'quran_individual',
      title: 'اشتراك تحفيظ فردي',
      teacher: teachers[0],
      status: 'active',
      totalSessions: 12,
      usedSessions: 5,
      expiresAt: DateTime.now().add(const Duration(days: 30)),
      autoRenewal: true,
    ),
    MockSubscription(
      id: '2',
      type: 'academic',
      title: 'باقة الرياضيات الشهرية',
      teacher: teachers[2],
      status: 'active',
      totalSessions: 8,
      usedSessions: 3,
      expiresAt: DateTime.now().add(const Duration(days: 20)),
      autoRenewal: false,
    ),
    MockSubscription(
      id: '3',
      type: 'course',
      title: 'دورة المحادثة الإنجليزية',
      teacher: teachers[3],
      status: 'active',
      totalSessions: 20,
      usedSessions: 5,
      autoRenewal: false,
    ),
  ];

  // Payments
  static List<MockPayment> get payments => [
    MockPayment(
      id: '1',
      code: 'PAY-2024-001',
      amount: 500,
      date: DateTime.now().subtract(const Duration(days: 5)),
      status: 'completed',
      subscriptionTitle: 'دورة المحادثة الإنجليزية',
    ),
    MockPayment(
      id: '2',
      code: 'PAY-2024-002',
      amount: 300,
      date: DateTime.now().subtract(const Duration(days: 15)),
      status: 'completed',
      subscriptionTitle: 'اشتراك تحفيظ فردي',
    ),
  ];

  // Certificates
  static List<MockCertificate> get certificates => [
    MockCertificate(
      id: '1',
      title: 'شهادة إتمام حفظ جزء عم',
      type: 'quran',
      issuedAt: DateTime.now().subtract(const Duration(days: 30)),
      certificateNumber: 'CERT-Q-2024-001',
    ),
    MockCertificate(
      id: '2',
      title: 'شهادة اجتياز دورة أساسيات اللغة الإنجليزية',
      type: 'course',
      issuedAt: DateTime.now().subtract(const Duration(days: 60)),
      certificateNumber: 'CERT-C-2024-015',
    ),
  ];

  // Stats
  static Map<String, dynamic> get studentStats => {
    'nextSession': sessions.firstWhere((s) => s.status == 'scheduled', orElse: () => sessions.first),
    'pendingHomework': homework.where((h) => h.status == 'pending').length,
    'pendingQuizzes': quizzes.where((q) => q.status == 'available').length,
    'attendanceRate': 92,
    'completedSessions': 45,
    'totalSessions': 50,
  };
}
