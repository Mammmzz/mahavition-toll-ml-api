import 'package:flutter/material.dart';
import 'dart:async';
import 'package:flutter/services.dart';
import 'login_page.dart';
import '../utils/constants.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _opacity;
  late Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    
    // Set status bar color to transparent
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
    );

    _controller = AnimationController(
      duration: const Duration(seconds: 3),
      vsync: this,
    );

    _opacity = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.65, curve: Curves.easeInOut),
      ),
    );

    _scale = Tween<double>(begin: 0.5, end: 1).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.65, curve: Curves.easeInOut),
      ),
    );

    _controller.forward();

    Timer(const Duration(seconds: 3), () {
      Navigator.of(context).pushReplacement(
        PageRouteBuilder(
          pageBuilder: (context, animation, secondaryAnimation) => const LoginPage(),
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            const begin = Offset(1.0, 0.0);
            const end = Offset.zero;
            const curve = Curves.easeInOut;

            var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
            var offsetAnimation = animation.drive(tween);

            return SlideTransition(
              position: offsetAnimation, 
              child: FadeTransition(
                opacity: animation,
                child: child,
              )
            );
          },
          transitionDuration: const Duration(milliseconds: 800),
        ),
      );
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppColors.primaryColor,
              AppColors.secondaryColor,
            ],
          ),
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            // Animated Background Particles
            Positioned.fill(
              child: AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  return CustomPaint(
                    painter: ParticlePainter(
                      animationValue: _controller.value,
                    ),
                    child: Container(),
                  );
                },
              ),
            ),
            
            // Logo and App Name
            AnimatedBuilder(
              animation: _controller,
              builder: (context, child) {
                return Transform.scale(
                  scale: _scale.value,
                  child: Opacity(
                    opacity: _opacity.value,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Logo
                        Container(
                          width: 140,
                          height: 140,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(30),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withAlpha(51),
                                blurRadius: 20,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(15.0),
                            child: const Icon(
                              Icons.toll_rounded,
                              size: 80,
                              color: AppColors.primaryColor,
                            ),
                          ),
                        ),
                        
                        const SizedBox(height: 30),
                        
                        // App Name
                        const Text(
                          AppStrings.appName,
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 38,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 1.5,
                          ),
                        ),
                        
                        const SizedBox(height: 10),
                        
                        // Tagline
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                          decoration: BoxDecoration(
                            color: Colors.white.withAlpha(38),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text(
                            "Jalan Tol Tanpa Henti",
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
            
            // Version
            Positioned(
              bottom: 30,
              child: AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  return Opacity(
                    opacity: _controller.value,
                    child: const Text(
                      "v1.0.0",
                      style: TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Custom Painter for animated particles
class ParticlePainter extends CustomPainter {
  final double animationValue;

  ParticlePainter({required this.animationValue});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white.withAlpha(51)
      ..style = PaintingStyle.fill;

    final particlePositions = [
      [0.1, 0.2, 0.01 + animationValue * 0.03],
      [0.3, 0.6, 0.02 + animationValue * 0.02],
      [0.8, 0.3, 0.015 + animationValue * 0.025],
      [0.6, 0.8, 0.02 + animationValue * 0.015],
      [0.2, 0.4, 0.025 + animationValue * 0.02],
      [0.7, 0.7, 0.015 + animationValue * 0.025],
      [0.9, 0.5, 0.02 + animationValue * 0.01],
      [0.4, 0.1, 0.01 + animationValue * 0.03],
      [0.5, 0.5, 0.025 + animationValue * 0.015],
    ];

    for (var particle in particlePositions) {
      final offsetX = particle[0] * size.width;
      final offsetY = (particle[1] - animationValue * 0.2) * size.height % size.height;
      final radius = particle[2] * size.width;
      
      canvas.drawCircle(
        Offset(offsetX, offsetY),
        radius,
        paint,
      );
    }
    
    // Draw additional particles
    final smallParticlePaint = Paint()
      ..color = Colors.white.withAlpha(26)
      ..style = PaintingStyle.fill;
      
    for (var i = 0; i < 30; i++) {
      final x = (i * 37 % 100) / 100 * size.width;
      final y = (i * 23 % 100 + animationValue * 15) % 100 / 100 * size.height;
      final radius = (i % 5 + 1) * size.width * 0.003;
      
      canvas.drawCircle(Offset(x, y), radius, smallParticlePaint);
    }
  }

  @override
  bool shouldRepaint(ParticlePainter oldDelegate) => true;
}
