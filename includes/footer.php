        </div><!-- /.container -->
    </main><!-- /.main-content -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="<?php echo url('index.php'); ?>" class="footer-brand">🐾 PawConnect</a>
                    <p style="max-width: 400px; color: #A0AEC0;">
                        PawConnect brings verified animal shelters and loving prospective adopters together. Every companion deserves a warm home and a caring family.
                    </p>
                </div>

                <div>
                    <h4 style="color: #FFF; margin-bottom: 15px;">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo url('index.php'); ?>">Home</a></li>
                        <li><a href="<?php echo url('pets.php'); ?>">Browse All Pets</a></li>
                        <li><a href="<?php echo url('register.php'); ?>">Shelter & Adopter Signup</a></li>
                        <li><a href="<?php echo url('login.php'); ?>">Portal Login</a></li>
                    </ul>
                </div>

                <div>
                    <h4 style="color: #FFF; margin-bottom: 15px;">Contact & Support</h4>
                    <p style="color: #A0AEC0; font-size: 0.9rem;">
                        📍 Kerala, India<br>
                        📞 +91 98765 43210<br>
                        ✉️ support@pawconnect.com
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> PawConnect Pet Adoption Portal. College Academic Project.</p>
            </div>
        </div>
    </footer>

    <!-- Vanilla JS for mobile navbar toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('navToggle');
            const menu = document.getElementById('navMenu');
            if (toggle && menu) {
                toggle.addEventListener('click', function() {
                    menu.classList.toggle('open');
                });
            }
        });
    </script>
</body>
</html>
