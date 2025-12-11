/// Itqan Platform Route Definitions
class AppRoutes {
  AppRoutes._();

  // ============================================
  // ROOT ROUTES
  // ============================================
  static const String splash = '/';
  static const String roleSelection = '/role-selection';
  static const String login = '/login';
  static const String register = '/register';

  // ============================================
  // STUDENT ROUTES
  // ============================================
  static const String student = '/student';
  static const String studentHome = '/student/home';
  static const String studentSessions = '/student/sessions';
  static const String studentSessionDetail = '/student/sessions/:id';
  static const String studentCircles = '/student/circles';
  static const String studentCircleDetail = '/student/circles/:id';
  static const String studentCourses = '/student/courses';
  static const String studentCourseDetail = '/student/courses/:id';
  static const String studentCourseSessions = '/student/courses/:courseId/sessions';
  static const String studentCourseSessionDetail = '/student/courses/:courseId/sessions/:sessionId';
  static const String studentHomework = '/student/homework';
  static const String studentHomeworkDetail = '/student/homework/:id';
  static const String studentQuizzes = '/student/quizzes';
  static const String studentQuizTake = '/student/quizzes/:id/take';
  static const String studentQuizResult = '/student/quizzes/:id/result';
  static const String studentSubscriptions = '/student/subscriptions';
  static const String studentSubscriptionDetail = '/student/subscriptions/:id';
  static const String studentPayments = '/student/payments';
  static const String studentPaymentDetail = '/student/payments/:id';
  static const String studentCertificates = '/student/certificates';
  static const String studentCertificateDetail = '/student/certificates/:id';
  static const String studentCalendar = '/student/calendar';
  static const String studentProfile = '/student/profile';
  static const String studentProfileEdit = '/student/profile/edit';
  static const String studentTeachers = '/student/teachers';
  static const String studentTeacherDetail = '/student/teachers/:id';
  static const String studentSettings = '/student/settings';

  // ============================================
  // TEACHER ROUTES
  // ============================================
  static const String teacher = '/teacher';
  static const String teacherHome = '/teacher/home';
  static const String teacherStudents = '/teacher/students';
  static const String teacherStudentDetail = '/teacher/students/:id';
  static const String teacherCircles = '/teacher/circles';
  static const String teacherCircleDetail = '/teacher/circles/:id';
  static const String teacherCircleProgress = '/teacher/circles/:id/progress';
  static const String teacherSessions = '/teacher/sessions';
  static const String teacherSessionDetail = '/teacher/sessions/:id';
  static const String teacherHomework = '/teacher/homework';
  static const String teacherHomeworkGrade = '/teacher/homework/:id/grade';
  static const String teacherEarnings = '/teacher/earnings';
  static const String teacherProfile = '/teacher/profile';
  static const String teacherProfileEdit = '/teacher/profile/edit';
  static const String teacherSettings = '/teacher/settings';

  // ============================================
  // PARENT ROUTES
  // ============================================
  static const String parent = '/parent';
  static const String parentHome = '/parent/home';
  static const String parentChildren = '/parent/children';
  static const String parentChildDetail = '/parent/children/:id';
  static const String parentSessions = '/parent/sessions';
  static const String parentSessionDetail = '/parent/sessions/:id';
  static const String parentReports = '/parent/reports';
  static const String parentReportDetail = '/parent/reports/:id';
  static const String parentHomework = '/parent/homework';
  static const String parentQuizzes = '/parent/quizzes';
  static const String parentPayments = '/parent/payments';
  static const String parentCertificates = '/parent/certificates';
  static const String parentProfile = '/parent/profile';
  static const String parentProfileEdit = '/parent/profile/edit';
  static const String parentSettings = '/parent/settings';

  // ============================================
  // HELPER METHODS
  // ============================================

  /// Build student session detail path
  static String studentSessionPath(String id) => '/student/sessions/$id';

  /// Build student circle detail path
  static String studentCirclePath(String id) => '/student/circles/$id';

  /// Build student course detail path
  static String studentCoursePath(String id) => '/student/courses/$id';

  /// Build student homework detail path
  static String studentHomeworkPath(String id) => '/student/homework/$id';

  /// Build student quiz take path
  static String studentQuizTakePath(String id) => '/student/quizzes/$id/take';

  /// Build student quiz result path
  static String studentQuizResultPath(String id) => '/student/quizzes/$id/result';

  /// Build student subscription detail path
  static String studentSubscriptionPath(String id) => '/student/subscriptions/$id';

  /// Build student payment detail path
  static String studentPaymentPath(String id) => '/student/payments/$id';

  /// Build student certificate detail path
  static String studentCertificatePath(String id) => '/student/certificates/$id';

  /// Build student teacher detail path
  static String studentTeacherPath(String id) => '/student/teachers/$id';

  /// Build teacher student detail path
  static String teacherStudentPath(String id) => '/teacher/students/$id';

  /// Build teacher circle detail path
  static String teacherCirclePath(String id) => '/teacher/circles/$id';

  /// Build teacher session detail path
  static String teacherSessionPath(String id) => '/teacher/sessions/$id';

  /// Build teacher homework grade path
  static String teacherHomeworkGradePath(String id) => '/teacher/homework/$id/grade';

  /// Build parent child detail path
  static String parentChildPath(String id) => '/parent/children/$id';

  /// Build parent session detail path
  static String parentSessionPath(String id) => '/parent/sessions/$id';

  /// Build parent report detail path
  static String parentReportPath(String id) => '/parent/reports/$id';
}
