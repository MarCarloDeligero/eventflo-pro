<?php
// about.php
require_once '../includes/config.php';

$page_title = "About EventFlow Pro";
include '../templates/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">About EventFlow Pro</h1>
                <p class="lead mb-4">Transforming the way events are created, managed, and experienced.</p>
            </div>
            <div class="col-lg-6 text-center">
                <img src="/eventflow-pro/assets/images/about.png" alt="About EventFlow Pro" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="h1 mb-4">Our Story</h2>
                <p class="lead text-muted mb-4">
                    EventFlow Pro was born from a simple observation: organizing events should be effortless, 
                    whether you're hosting a small workshop or a large conference.
                </p>
                <p class="text-muted">
                    Founded in 2024, our platform has helped thousands of event organizers, businesses, 
                    and communities create memorable experiences. We believe that great events have the 
                    power to connect people, share knowledge, and create lasting memories.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-5">
            <div class="col-md-6">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-bullseye fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Our Mission</h3>
                    <p class="text-muted">
                        To empower event organizers with intuitive tools that simplify event management, 
                        while providing attendees with seamless registration and engagement experiences.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-eye fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Our Vision</h3>
                    <p class="text-muted">
                        To become the leading platform for event management, fostering connections and 
                        enabling communities to thrive through well-organized, accessible events.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What We Offer -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center h1 mb-5">What We Offer</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-calendar-plus fa-2x"></i>
                        </div>
                        <h4 class="h5 mb-3">Easy Event Creation</h4>
                        <p class="text-muted">
                            Create beautiful event pages in minutes with our intuitive event builder.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-ticket-alt fa-2x"></i>
                        </div>
                        <h4 class="h5 mb-3">Seamless Registration</h4>
                        <p class="text-muted">
                            Streamline attendee registration with customizable forms and secure payments.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                        <h4 class="h5 mb-3">Powerful Analytics</h4>
                        <p class="text-muted">
                            Gain insights with real-time analytics and attendance tracking.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">500+</h3>
                    <p class="mb-0">Events Hosted</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">10K+</h3>
                    <p class="mb-0">Happy Attendees</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">200+</h3>
                    <p class="mb-0">Organizers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">98%</h3>
                    <p class="mb-0">Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section (Optional) -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center h1 mb-5">Meet Our Team</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                        <h4 class="h5 mb-1">Elmark Omasdang</h4>
                        <p class="text-muted mb-2">Founder & CEO</p>
                        <p class="small text-muted">
                            Passionate about creating solutions that bring people together through technology.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                        <h4 class="h5 mb-1">Christine Dionsay</h4>
                        <p class="text-muted mb-2">Head of Product</p>
                        <p class="small text-muted">
                            Dedicated to building user-friendly experiences that solve real problems.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                        <h4 class="h5 mb-1">Jenelyn Calimpong</h4>
                        <p class="text-muted mb-2">Lead Developer</p>
                        <p class="small text-muted">
                            Crafting robust and scalable solutions to power amazing event experiences.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                        <h4 class="h5 mb-1">Nach Baguio Laranjo</h4>
                        <p class="text-muted mb-2">Lead Developer</p>
                        <p class="small text-muted">
                            Crafting robust and scalable solutions to power amazing event experiences.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="h1 mb-4">Ready to Get Started?</h2>
                <p class="lead text-muted mb-4">
                    Join thousands of organizers who are already creating amazing events with EventFlow Pro.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="/eventflow-pro/pages/register.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-rocket me-2"></i>Start Your Journey
                    </a>
                    <a href="/eventflow-pro/pages/contact.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-envelope me-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../templates/footer.php'; ?>