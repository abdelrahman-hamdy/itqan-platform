import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'routes.dart';
import '../../features/auth/presentation/pages/splash_page.dart';
import '../../features/auth/presentation/pages/role_selection_page.dart';
import '../../features/auth/presentation/pages/login_page.dart';
import '../../features/student/presentation/pages/student_shell.dart';
import '../../features/student/presentation/pages/dashboard_page.dart';
import '../../features/student/presentation/pages/sessions_page.dart';
import '../../features/student/presentation/pages/session_detail_page.dart';
import '../../features/student/presentation/pages/circles_page.dart';
import '../../features/student/presentation/pages/circle_detail_page.dart';
import '../../features/student/presentation/pages/courses_page.dart';
import '../../features/student/presentation/pages/course_detail_page.dart';
import '../../features/student/presentation/pages/homework_page.dart';
import '../../features/student/presentation/pages/homework_detail_page.dart';
import '../../features/student/presentation/pages/quizzes_page.dart';
import '../../features/student/presentation/pages/quiz_take_page.dart';
import '../../features/student/presentation/pages/quiz_result_page.dart';
import '../../features/student/presentation/pages/subscriptions_page.dart';
import '../../features/student/presentation/pages/payments_page.dart';
import '../../features/student/presentation/pages/certificates_page.dart';
import '../../features/student/presentation/pages/calendar_page.dart';
import '../../features/student/presentation/pages/profile_page.dart';
import '../../features/student/presentation/pages/teachers_page.dart';
import '../../features/student/presentation/pages/teacher_detail_page.dart';
import '../../features/student/presentation/pages/more_page.dart';
import '../../features/teacher/presentation/pages/teacher_shell.dart';
import '../../features/teacher/presentation/pages/teacher_dashboard_page.dart';
import '../../features/parent/presentation/pages/parent_shell.dart';
import '../../features/parent/presentation/pages/parent_dashboard_page.dart';

/// Global navigation key
final GlobalKey<NavigatorState> _rootNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'root');
final GlobalKey<NavigatorState> _studentShellNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'studentShell');
final GlobalKey<NavigatorState> _teacherShellNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'teacherShell');
final GlobalKey<NavigatorState> _parentShellNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'parentShell');

/// App Router Configuration
class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: AppRoutes.splash,
    debugLogDiagnostics: true,
    routes: [
      // ============================================
      // AUTH ROUTES
      // ============================================
      GoRoute(
        path: AppRoutes.splash,
        name: 'splash',
        builder: (context, state) => const SplashPage(),
      ),
      GoRoute(
        path: AppRoutes.roleSelection,
        name: 'roleSelection',
        builder: (context, state) => const RoleSelectionPage(),
      ),
      GoRoute(
        path: AppRoutes.login,
        name: 'login',
        builder: (context, state) {
          final role = state.uri.queryParameters['role'] ?? 'student';
          return LoginPage(role: role);
        },
      ),

      // ============================================
      // STUDENT ROUTES (with Shell)
      // ============================================
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return StudentShell(navigationShell: navigationShell);
        },
        branches: [
          // Home Tab
          StatefulShellBranch(
            navigatorKey: _studentShellNavigatorKey,
            routes: [
              GoRoute(
                path: AppRoutes.studentHome,
                name: 'studentHome',
                builder: (context, state) => const StudentDashboardPage(),
                routes: [
                  // Nested routes from home
                  GoRoute(
                    path: 'teachers',
                    name: 'studentTeachersFromHome',
                    builder: (context, state) => const StudentTeachersPage(),
                  ),
                ],
              ),
            ],
          ),
          // Sessions Tab
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: AppRoutes.studentSessions,
                name: 'studentSessions',
                builder: (context, state) => const StudentSessionsPage(),
                routes: [
                  GoRoute(
                    path: ':id',
                    name: 'studentSessionDetail',
                    builder: (context, state) {
                      final id = state.pathParameters['id']!;
                      return StudentSessionDetailPage(sessionId: id);
                    },
                  ),
                ],
              ),
            ],
          ),
          // Courses Tab
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: AppRoutes.studentCourses,
                name: 'studentCourses',
                builder: (context, state) => const StudentCoursesPage(),
                routes: [
                  GoRoute(
                    path: ':id',
                    name: 'studentCourseDetail',
                    builder: (context, state) {
                      final id = state.pathParameters['id']!;
                      return StudentCourseDetailPage(courseId: id);
                    },
                  ),
                ],
              ),
            ],
          ),
          // Homework Tab
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: AppRoutes.studentHomework,
                name: 'studentHomework',
                builder: (context, state) => const StudentHomeworkPage(),
                routes: [
                  GoRoute(
                    path: ':id',
                    name: 'studentHomeworkDetail',
                    builder: (context, state) {
                      final id = state.pathParameters['id']!;
                      return StudentHomeworkDetailPage(homeworkId: id);
                    },
                  ),
                ],
              ),
            ],
          ),
          // More Tab
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/student/more',
                name: 'studentMore',
                builder: (context, state) => const StudentMorePage(),
                routes: [
                  GoRoute(
                    path: 'profile',
                    name: 'studentProfile',
                    builder: (context, state) => const StudentProfilePage(),
                  ),
                  GoRoute(
                    path: 'circles',
                    name: 'studentCircles',
                    builder: (context, state) => const StudentCirclesPage(),
                    routes: [
                      GoRoute(
                        path: ':id',
                        name: 'studentCircleDetail',
                        builder: (context, state) {
                          final id = state.pathParameters['id']!;
                          return StudentCircleDetailPage(circleId: id);
                        },
                      ),
                    ],
                  ),
                  GoRoute(
                    path: 'quizzes',
                    name: 'studentQuizzes',
                    builder: (context, state) => const StudentQuizzesPage(),
                    routes: [
                      GoRoute(
                        path: ':id/take',
                        name: 'studentQuizTake',
                        builder: (context, state) {
                          final id = state.pathParameters['id']!;
                          return StudentQuizTakePage(quizId: id);
                        },
                      ),
                      GoRoute(
                        path: ':id/result',
                        name: 'studentQuizResult',
                        builder: (context, state) {
                          final id = state.pathParameters['id']!;
                          return StudentQuizResultPage(quizId: id);
                        },
                      ),
                    ],
                  ),
                  GoRoute(
                    path: 'subscriptions',
                    name: 'studentSubscriptions',
                    builder: (context, state) => const StudentSubscriptionsPage(),
                  ),
                  GoRoute(
                    path: 'payments',
                    name: 'studentPayments',
                    builder: (context, state) => const StudentPaymentsPage(),
                  ),
                  GoRoute(
                    path: 'certificates',
                    name: 'studentCertificates',
                    builder: (context, state) => const StudentCertificatesPage(),
                  ),
                  GoRoute(
                    path: 'calendar',
                    name: 'studentCalendar',
                    builder: (context, state) => const StudentCalendarPage(),
                  ),
                  GoRoute(
                    path: 'teachers',
                    name: 'studentTeachers',
                    builder: (context, state) => const StudentTeachersPage(),
                    routes: [
                      GoRoute(
                        path: ':id',
                        name: 'studentTeacherDetail',
                        builder: (context, state) {
                          final id = state.pathParameters['id']!;
                          return StudentTeacherDetailPage(teacherId: id);
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ],
      ),

      // ============================================
      // TEACHER ROUTES (with Shell)
      // ============================================
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return TeacherShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            navigatorKey: _teacherShellNavigatorKey,
            routes: [
              GoRoute(
                path: AppRoutes.teacherHome,
                name: 'teacherHome',
                builder: (context, state) => const TeacherDashboardPage(),
              ),
            ],
          ),
          // Add more teacher branches as needed
        ],
      ),

      // ============================================
      // PARENT ROUTES (with Shell)
      // ============================================
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return ParentShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            navigatorKey: _parentShellNavigatorKey,
            routes: [
              GoRoute(
                path: AppRoutes.parentHome,
                name: 'parentHome',
                builder: (context, state) => const ParentDashboardPage(),
              ),
            ],
          ),
          // Add more parent branches as needed
        ],
      ),
    ],
  );
}
