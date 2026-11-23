<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events | College Connect</title>

    <link rel="stylesheet" href="assets/css/event.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
   <?php include 'nav.php'; ?>
    <main class="events-page">

        <!-- HERO SECTION -->
        <section class="events-hero">

            <div class="hero-content">
                <span class="hero-tag">
                    <i data-lucide="calendar-days"></i>
                    Campus Life & Events
                </span>

                <h1>Discover What's<br>
                    Happening on <span>Campus.</span>
                </h1>

                <p>
                    Stay in the loop with campus events, workshops, competitions
                    and unforgettable moments happening around your college.
                </p>

                <div class="hero-stats">
                    <div>
                        <i data-lucide="calendar"></i>
                        <span>Upcoming Events</span>
                    </div>

                    <div>
                        <i data-lucide="users"></i>
                        <span>Campus Community</span>
                    </div>
                </div>
            </div>

            <!-- HERO VISUAL -->
            <div class="hero-visual">

                <div class="hero-orbit orbit-one"></div>
                <div class="hero-orbit orbit-two"></div>

                <div class="hero-main-icon">
                    <i data-lucide="party-popper"></i>
                </div>

                <div class="floating-icon float-calendar">
                    <i data-lucide="calendar-days"></i>
                </div>

                <div class="floating-icon float-music">
                    <i data-lucide="music-2"></i>
                </div>

                <div class="floating-icon float-trophy">
                    <i data-lucide="trophy"></i>
                </div>

                <div class="floating-icon float-light">
                    <i data-lucide="sparkles"></i>
                </div>

                <div class="hero-caption">
                    <i data-lucide="star"></i>
                    Make memories. Explore. Connect.
                </div>

            </div>

        </section>

        <div class="events-main-container">
            <!-- FEATURED EVENT -->
            <section class="featured-section">

                <div class="section-heading">
                    <div>
                        <span class="section-label">DON'T MISS OUT</span>
                        <h2>Featured Event</h2>
                    </div>
                </div>

                <div class="featured-card">

                    <div class="featured-art">

                        <div class="featured-art-content">
                            <span class="featured-chip">FEATURED</span>

                            <i data-lucide="zap" class="featured-zap"></i>

                            <h2>LEGACY.<br>EXCELLENCE.<br>CELEBRATION.</h2>

                            <p>AITAM Silver Jubilee</p>
                        </div>

                        <div class="featured-decoration decor-circle"></div>
                        <div class="featured-decoration decor-ring"></div>

                    </div>

                    <div class="featured-info">

                        <div class="event-category celebration">
                            <i data-lucide="party-popper"></i>
                            <span>AITAM Silver Jubilee</span>
                        </div>

                        <h3>AITAM Silver Jubilee Valedictory Celebrations</h3>

                        <p>
                            Celebrating 25 years of excellence, innovation, and unforgettable
                            memories! AITAM Silver Jubilee marks a proud milestone, bringing
                            together students, faculty, alumni, and the entire college community
                            to celebrate its remarkable journey, achievements, and legacy.
                            A celebration of the past, a joy of the present, and a vision
                            for the future.
                        </p>

                        <div class="featured-meta">

                            <div>
                                <i data-lucide="calendar-days"></i>
                                <span>December 19, 2026</span>
                            </div>

                            <div>
                                <i data-lucide="clock"></i>
                                <span>9:00 AM onwards</span>
                            </div>

                            <div>
                                <i data-lucide="map-pin"></i>
                                <span>College Campus</span>
                            </div>

                        </div>

                        <button class="details-button" onclick="openEvent('featured')">
                            Explore Event
                            <i data-lucide="arrow-up-right"></i>
                        </button>

                    </div>
                </div>

            </section>


            <!-- UPCOMING EVENTS -->
            <section class="events-list-section" id="upcoming">

                <div class="section-heading events-heading">

                    <div>
                        <span class="section-label">CAMPUS CALENDAR</span>
                        <h2>Upcoming Events</h2>
                        <p>Explore events, find your interests and be part of campus life.</p>
                    </div>

                    <div class="event-count" id="eventCount">
                        8 Events
                    </div>

                </div>


                <!-- SEARCH -->
                <div class="event-toolbar">

                    <div class="search-box">
                        <i data-lucide="search"></i>

                        <input type="text" id="eventSearch" placeholder="Search events by name..." autocomplete="off">

                        <button id="clearSearch" aria-label="Clear search">
                            <i data-lucide="x"></i>
                        </button>
                    </div>

                </div>


                <!-- FILTERS -->
                <div class="event-filters" id="eventFilters">

                    <button class="filter-btn active" data-filter="all">
                        All Events
                    </button>

                    <button class="filter-btn" data-filter="technical">
                        <i data-lucide="cpu"></i>
                        Technical
                    </button>

                    <button class="filter-btn" data-filter="cultural">
                        <i data-lucide="music"></i>
                        Cultural
                    </button>

                    <button class="filter-btn" data-filter="sports">
                        <i data-lucide="trophy"></i>
                        Sports
                    </button>

                    <button class="filter-btn" data-filter="workshop">
                        <i data-lucide="lightbulb"></i>
                        Workshops
                    </button>

                </div>


                <!-- UPCOMING EVENT CARDS -->
                <div class="events-grid" id="eventsGrid">


                    <!-- EVENT 1: AVISHKAAR VIRTUAL -->
                    <article class="event-card" data-category="technical"
                        data-title="AVISHKAAR Season IV Virtual Hackathon">

                        <div class="event-cover cover-tech">
                            <span class="cover-label">TECHNICAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="code-2"></i>
                            </div>

                            <div class="cover-title">
                                <span>24-HOUR SPRINT</span>
                                <h3>AVISHKAAR<br>SEASON IV</h3>
                            </div>

                            <span class="cover-year">ONLINE</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">NOV</span>
                                <span class="date-day">27</span>
                            </div>

                            <div class="event-card-content">
                                <h3>AVISHKAAR – Virtual Hackathon</h3>

                                <p>
                                    National-level virtual hackathon featuring a 24-hour
                                    coding sprint and a ₹50,000 prize pool.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="clock"></i>
                                        24 Hours
                                    </span>

                                    <span>
                                        <i data-lucide="globe"></i>
                                        Online
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('virtual')"
                                aria-label="View Virtual Hackathon details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 2: AVISHKAAR PHYSICAL -->
                    <article class="event-card" data-category="technical"
                        data-title="AVISHKAAR Season IV Physical Hackathon">

                        <div class="event-cover cover-project">
                            <span class="cover-label">TECHNICAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="rocket"></i>
                            </div>

                            <div class="cover-title">
                                <span>48-HOUR MARATHON</span>
                                <h3>AVISHKAAR<br>PHYSICAL</h3>
                            </div>

                            <span class="cover-year">TEKKALI</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">DEC</span>
                                <span class="date-day">24</span>
                            </div>

                            <div class="event-card-content">
                                <h3>AVISHKAAR – Physical Hackathon</h3>

                                <p>
                                    A 48-hour on-campus hackathon featuring innovation,
                                    teamwork and a ₹3,000 prize pool.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="clock"></i>
                                        48 Hours
                                    </span>

                                    <span>
                                        <i data-lucide="map-pin"></i>
                                        Tekkali Campus
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('physical')"
                                aria-label="View Physical Hackathon details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 3: ANNUAL TECH EVENTS -->
                    <article class="event-card" data-category="technical"
                        data-title="Techno-Vision and Go-Karting Championship">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">TECHNICAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="trophy"></i>
                            </div>

                            <div class="cover-title">
                                <span>COMING IN 2027</span>
                                <h3>ANNUAL TECH<br>EVENTS</h3>
                            </div>

                            <span class="cover-year">2027</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">FEB</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Annual Tech Events</h3>

                                <p>
                                    Techno-Vision coding and debugging contests in February
                                    and AITAM's Go-Karting Championship in March.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="calendar"></i>
                                        Feb – Mar 2027
                                    </span>

                                    <span>
                                        <i data-lucide="zap"></i>
                                        Upcoming
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('annual')"
                                aria-label="View Annual Tech Events details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 4: ACT-O-MANIA -->
                    <article class="event-card" data-category="cultural"
                        data-title="Act-o-Mania One-Act Play Competition">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">CULTURAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="drama"></i>
                            </div>

                            <div class="cover-title">
                                <span>THEATRICAL STORYTELLING</span>
                                <h3>ACT-O-<br>MANIA</h3>
                            </div>

                            <span class="cover-year">2026</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">NOV</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Act-o-Mania</h3>

                                <p>
                                    One-Act Play Competition showcasing student drama,
                                    creativity and theatrical storytelling.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="theater"></i>
                                        Drama
                                    </span>

                                    <span>
                                        <i data-lucide="calendar"></i>
                                        November 2026
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('actomania')"
                                aria-label="View Act-o-Mania details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 5: CULTURAL CLUB -->
                    <article class="event-card" data-category="cultural" data-title="Cultural and Arts Club Sessions">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">CULTURAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="music"></i>
                            </div>

                            <div class="cover-title">
                                <span>EXPRESS YOUR TALENT</span>
                                <h3>CULTURAL<br>CLUB SESSIONS</h3>
                            </div>

                            <span class="cover-year">WEEKLY</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">MON</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Cultural & Arts Club Sessions</h3>

                                <p>
                                    Regular sessions for dance, drama, painting and vocal
                                    music to explore and develop your creative talents.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="clock"></i>
                                        4:30–6:30 PM
                                    </span>

                                    <span>
                                        <i data-lucide="map-pin"></i>
                                        OAT / Studio
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('artsessions')"
                                aria-label="View Cultural Club Sessions details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 6: SANKRANTHI -->
                    <article class="event-card" data-category="cultural" data-title="Sankranthi Sambaralu">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">CULTURAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="sun"></i>
                            </div>

                            <div class="cover-title">
                                <span>TRADITION & CELEBRATION</span>
                                <h3>SANKRANTHI<br>SAMBARALU</h3>
                            </div>

                            <span class="cover-year">2027</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">JAN</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Sankranthi Sambaralu</h3>

                                <p>
                                    Celebrate the traditional harvest festival with
                                    festive activities, culture and campus-wide celebrations.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="calendar"></i>
                                        January 2027
                                    </span>

                                    <span>
                                        <i data-lucide="users"></i>
                                        Campus Celebration
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('sankranthi')"
                                aria-label="View Sankranthi Sambaralu details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 7: SPORTS -->
                    <article class="event-card" data-category="sports" data-title="AITAM Campus Sports Activities">

                        <div class="event-cover cover-sports">
                            <span class="cover-label">SPORTS</span>

                            <div class="cover-graphic">
                                <i data-lucide="trophy"></i>
                            </div>

                            <div class="cover-title">
                                <span>TEAMWORK & SPORTSMANSHIP</span>
                                <h3>CAMPUS<br>SPORTS</h3>
                            </div>

                            <span class="cover-year">2027</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">TBA</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>AITAM Campus Sports Activities</h3>

                                <p>
                                    Participate in exciting campus sports activities,
                                    athletic competitions and team games. Show your
                                    talent, teamwork and sportsmanship.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="calendar"></i>
                                        Dates to be announced
                                    </span>

                                    <span>
                                        <i data-lucide="map-pin"></i>
                                        College Sports Ground
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('sports')" aria-label="View Sports details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>


                    <!-- EVENT 8: WORKSHOP -->
                    <article class="event-card" data-category="workshop" data-title="AI and Machine Learning Workshop">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">WORKSHOP</span>

                            <div class="cover-graphic">
                                <i data-lucide="lightbulb"></i>
                            </div>

                            <div class="cover-title">
                                <span>LEARN & INNOVATE</span>
                                <h3>AI & MACHINE<br>LEARNING</h3>
                            </div>

                            <span class="cover-year">2027</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">TBA</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>AI & Machine Learning Workshop</h3>

                                <p>
                                    Explore Artificial Intelligence and Machine Learning
                                    through practical demonstrations, interactive
                                    sessions and hands-on learning activities.
                                </p>

                                <div class="event-card-meta">
                                    <span>
                                        <i data-lucide="calendar"></i>
                                        Dates to be announced
                                    </span>

                                    <span>
                                        <i data-lucide="map-pin"></i>
                                        Computer Lab
                                    </span>
                                </div>
                            </div>

                            <button class="card-arrow" onclick="openEvent('ai')" aria-label="View Workshop details">
                                <i data-lucide="arrow-up-right"></i>
                            </button>

                        </div>
                    </article>

                </div>


                <!-- NO EVENTS FOUND -->
                <div class="no-events" id="noEvents">
                    <i data-lucide="calendar-x"></i>
                    <h3>No Events Found</h3>
                    <p>Try another category or search for a different event.</p>
                </div>

            </section>

            <!-- NEW PAST EVENTS SECTION -->
            <section class="past-list-section" id="pastEvents">

                <div class="past-list-heading">
                    <div>
                        <span class="section-label">CAMPUS HIGHLIGHTS</span>
                        <h2>Past Events</h2>
                        <p>
                            Look back at the events, achievements and memorable
                            experiences celebrated at our campus.
                        </p>
                    </div>
                </div>

                <div class="past-events-grid">


                    <!-- PAST EVENT 1: TECHNICAL -->
                    <article class="event-card">

                        <div class="event-cover cover-tech">
                            <span class="cover-label">TECHNICAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="code-2"></i>
                            </div>

                            <div class="cover-title">
                                <span>INNOVATION & CODING</span>
                                <h3>IDEATHON<br>2K24</h3>
                            </div>

                            <span class="cover-year">2024</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">2024</span>
                                <span class="date-day">24</span>
                            </div>

                            <div class="event-card-content">
                                <h3>IDEATHON 2K24 & Algorithmic Architects Hackathon</h3>

                                <p>
                                    Hosted by AITAM IIC and SSC Technical, encouraging
                                    B.Tech students to present innovative ideas and
                                    participate in coding challenges.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="cpu"></i> Technical</span>
                                    <span><i data-lucide="calendar"></i> 2024</span>
                                </div>
                            </div>

                        </div>
                    </article>


                    <!-- PAST EVENT 2: CULTURAL -->
                    <article class="event-card">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">CULTURAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="music"></i>
                            </div>

                            <div class="cover-title">
                                <span>SILVER JUBILEE</span>
                                <h3>AHLAAD<br>2K26</h3>
                            </div>

                            <span class="cover-year">2026</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">JUN</span>
                                <span class="date-day">26</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Ahlaad 2K26 – Silver Jubilee Cultural Fest</h3>

                                <p>
                                    A two-day cultural festival featuring dance,
                                    singing, rock band, short films, drama, photography,
                                    painting and other exciting competitions.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="music"></i> Cultural</span>
                                    <span><i data-lucide="calendar"></i> June 2026</span>
                                </div>
                            </div>

                        </div>
                    </article>


                    <!-- PAST EVENT 3: SPORTS -->
                    <article class="event-card">

                        <div class="event-cover cover-sports">
                            <span class="cover-label">SPORTS</span>

                            <div class="cover-graphic">
                                <i data-lucide="trophy"></i>
                            </div>

                            <div class="cover-title">
                                <span>SPORTSMANSHIP</span>
                                <h3>ANNUAL<br>SPORTS DAY</h3>
                            </div>

                            <span class="cover-year">CAMPUS</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">PAST</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Annual Sports Day</h3>

                                <p>
                                    Campus sporting activities and competitions
                                    encouraging teamwork, athletic talent and
                                    sportsmanship among students.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="trophy"></i> Sports</span>
                                    <span><i data-lucide="calendar"></i> Past Event</span>
                                </div>
                            </div>

                        </div>
                    </article>


                    <!-- PAST EVENT 4: WORKSHOP -->
                    <article class="event-card">

                        <div class="event-cover cover-workshop">
                            <span class="cover-label">WORKSHOP</span>

                            <div class="cover-graphic">
                                <i data-lucide="lightbulb"></i>
                            </div>

                            <div class="cover-title">
                                <span>LEARN & INNOVATE</span>
                                <h3>AI & ESSENTIAL<br>SKILLS</h3>
                            </div>

                            <span class="cover-year">2025</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">JUN</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Application Development with AI & Essential Skills</h3>

                                <p>
                                    A one-week national-level workshop conducted
                                    in collaboration with Braino Vision for
                                    faculty and students.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="lightbulb"></i> Workshop</span>
                                    <span><i data-lucide="calendar"></i> June 2025</span>
                                </div>
                            </div>

                        </div>
                    </article>


                    <!-- PAST EVENT 5: TECHNICAL -->
                    <article class="event-card">

                        <div class="event-cover cover-tech">
                            <span class="cover-label">TECHNICAL</span>

                            <div class="cover-graphic">
                                <i data-lucide="shield-check"></i>
                            </div>

                            <div class="cover-title">
                                <span>CYBERSECURITY</span>
                                <h3>CTF CYBER<br>CHALLENGE</h3>
                            </div>

                            <span class="cover-year">CAMPUS</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">PAST</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Capture the Flag (CTF) Cyber Challenge</h3>

                                <p>
                                    A hands-on cybersecurity challenge organized by
                                    the departments of CSC, CSD and BCA in collaboration
                                    with Supraja Technologies.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="cpu"></i> Technical</span>
                                    <span><i data-lucide="calendar"></i> Past Event</span>
                                </div>
                            </div>

                        </div>
                    </article>


                    <!-- PAST EVENT 6: WORKSHOP -->
                    <article class="event-card">

                        <div class="event-cover cover-tech">
                            <span class="cover-label">WORKSHOP</span>

                            <div class="cover-graphic">
                                <i data-lucide="cloud"></i>
                            </div>

                            <div class="cover-title">
                                <span>FACULTY DEVELOPMENT</span>
                                <h3>IBM CLOUD<br>DEVELOPMENT</h3>
                            </div>

                            <span class="cover-year">2024</span>
                        </div>

                        <div class="event-card-body">

                            <div class="event-date">
                                <span class="date-month">SEP</span>
                                <span class="date-day">--</span>
                            </div>

                            <div class="event-card-content">
                                <h3>Cloud Development using IBM Cloud</h3>

                                <p>
                                    A one-week Faculty Development Program organized
                                    in September 2024 in collaboration with
                                    NITTTR Chandigarh.
                                </p>

                                <div class="event-card-meta">
                                    <span><i data-lucide="cloud"></i> Workshop</span>
                                    <span><i data-lucide="calendar"></i> September 2024</span>
                                </div>
                            </div>

                        </div>
                    </article>

                </div>

            </section>


            <!-- ORIGINAL CAMPUS MEMORIES SECTION - UNCHANGED -->
            <section class="past-events-section">

                <div class="past-heading">
                    <div>
                        <span class="section-label">MOMENTS WE MADE</span>
                        <h2>Campus Memories</h2>
                        <p>A glimpse of the energy, creativity and moments shared on campus.</p>
                    </div>
                </div>

                <div class="memory-grid">

                    <div class="memory-card memory-one">
                        <i data-lucide="camera"></i>
                        <div>
                            <span>CREATIVITY & INNOVATION</span>
                            <h3>Ideas That Inspire</h3>
                        </div>
                    </div>

                    <div class="memory-card memory-two">
                        <i data-lucide="music"></i>
                        <div>
                            <span>CELEBRATION & CULTURE</span>
                            <h3>Moments of Joy</h3>
                        </div>
                    </div>

                    <div class="memory-card memory-three">
                        <i data-lucide="trophy"></i>
                        <div>
                            <span>TEAMWORK & PASSION</span>
                            <h3>Made to Remember</h3>
                        </div>
                    </div>

                </div>

            </section>
        </div>

        <!-- FINAL MESSAGE -->
        <section class="events-closing">

            <div class="closing-icon">
                <i data-lucide="heart-handshake"></i>
            </div>

            <h2>College Life Is More Than Classrooms.</h2>

            <p>
                It's about experiences, friendships, ideas and moments
                that bring us together.
            </p>

            <span class="closing-tag">
                Stay connected. Stay curious. Stay involved.
            </span>

        </section>


        <!-- EVENT DETAILS MODAL -->
        <div class="event-modal" id="eventModal">

            <div class="modal-overlay" onclick="closeEvent()"></div>

            <div class="modal-content">

                <button class="modal-close" onclick="closeEvent()" aria-label="Close event details">
                    <i data-lucide="x"></i>
                </button>

                <div class="modal-top-icon">
                    <i data-lucide="calendar-days"></i>
                </div>

                <span class="section-label" id="modalCategory">EVENT</span>

                <h2 id="modalTitle">Event Title</h2>

                <p id="modalDescription"></p>

                <div class="modal-details">

                    <div>
                        <i data-lucide="calendar"></i>
                        <span id="modalDate"></span>
                    </div>

                    <div>
                        <i data-lucide="clock"></i>
                        <span id="modalTime"></span>
                    </div>

                    <div>
                        <i data-lucide="map-pin"></i>
                        <span id="modalVenue"></span>
                    </div>

                </div>

                <div class="modal-note">
                    <i data-lucide="info"></i>
                    <p>
                        Event information is shared for campus awareness.
                        Stay updated for announcements and changes.
                    </p>
                </div>

            </div>

        </div>

    </main>


    <script>

        /* =================================
           SEARCH & FILTER
        ================================= */

        lucide.createIcons();

        const searchInput = document.getElementById("eventSearch");
        const clearSearch = document.getElementById("clearSearch");
        const filterButtons = document.querySelectorAll(".filter-btn");

        // Only select upcoming events, not Past Events.
        const eventCards = document.querySelectorAll("#eventsGrid .event-card");

        const eventCount = document.getElementById("eventCount");
        const noEvents = document.getElementById("noEvents");

        let activeFilter = "all";


        function filterEvents() {

            const searchText = searchInput.value.toLowerCase().trim();

            let visibleCount = 0;

            eventCards.forEach(card => {

                const category = card.dataset.category || "";
                const title = card.dataset.title || "";
                const cardText = card.innerText.toLowerCase();

                const matchesCategory =
                    activeFilter === "all" || category === activeFilter;

                const matchesSearch =
                    title.toLowerCase().includes(searchText) ||
                    cardText.includes(searchText);

                if (matchesCategory && matchesSearch) {

                    card.style.display = "";
                    visibleCount++;

                } else {

                    card.style.display = "none";

                }

            });

            eventCount.textContent =
                visibleCount + (visibleCount === 1 ? " Event" : " Events");

            noEvents.style.display =
                visibleCount === 0 ? "block" : "none";

            clearSearch.style.display =
                searchText ? "block" : "none";

        }


        searchInput.addEventListener("input", filterEvents);


        clearSearch.addEventListener("click", function () {

            searchInput.value = "";

            filterEvents();

            searchInput.focus();

        });


        filterButtons.forEach(button => {

            button.addEventListener("click", function () {

                filterButtons.forEach(btn => btn.classList.remove("active"));

                this.classList.add("active");

                activeFilter = this.dataset.filter;

                filterEvents();

            });

        });


        /* =================================
           EVENT DETAILS
        ================================= */

        const eventDetails = {

            featured: {
                category: "AITAM Silver Jubilee",
                title: "AITAM Silver Jubilee Valedictory Celebrations",
                description: "Celebrating 25 years of excellence, innovation, and unforgettable memories! AITAM Silver Jubilee marks a proud milestone, bringing together students, faculty, alumni, and the entire college community to celebrate its remarkable journey, achievements, and legacy. A celebration of the past, a joy of the present, and a vision for the future.",
                date: "December 19, 2026",
                time: "9:00 AM onwards",
                venue: "College Campus"
            },

            virtual: {
                category: "Technical – National-Level Hackathon",
                title: "AVISHKAAR – Season IV Virtual Hackathon",
                description: "ATAM's flagship innovation event featuring a 24-hour virtual hackathon sprint and a ₹50,000 prize pool.",
                date: "November 27, 2026",
                time: "24-Hour Sprint",
                venue: "Online"
            },

            physical: {
                category: "Technical – National-Level Hackathon",
                title: "AVISHKAAR – Season IV Physical Hackathon",
                description: "A 48-hour physical hackathon tentatively scheduled from December 24 to 26, 2026. Participants compete on campus in Tekkali, featuring a ₹3,000 prize pool.",
                date: "December 24–26, 2026 (Tentative)",
                time: "48-Hour Marathon",
                venue: "AITAM Campus, Tekkali"
            },

            annual: {
                category: "Technical – Annual Events",
                title: "Techno-Vision & Go-Karting Championship",
                description: "Upcoming annual events include Techno-Vision, featuring CSE coding and debugging contests typically held in February, and AITAM's Go-Karting Championship usually held in March.",
                date: "February – March 2027",
                time: "Dates to be announced",
                venue: "College Campus"
            },

            actomania: {
                category: "Cultural",
                title: "Act-o-Mania",
                description: "One-Act Play Competition showcasing student drama, creativity and theatrical storytelling.",
                date: "November 2026",
                time: "To be announced",
                venue: "College Campus"
            },

            artsessions: {
                category: "Cultural",
                title: "Cultural & Arts Club Sessions",
                description: "Regular sessions for dance, drama, painting and vocal music to explore and develop your creative talents.",
                date: "Weekly",
                time: "4:30–6:30 PM",
                venue: "OAT / Studio"
            },

            sankranthi: {
                category: "Cultural",
                title: "Sankranthi Sambaralu",
                description: "Celebrate the traditional harvest festival with festive activities, culture and campus-wide celebrations.",
                date: "January 2027",
                time: "To be announced",
                venue: "College Campus"
            },

            sports: {
                category: "Sports",
                title: "AITAM Campus Sports Activities",
                description: "Participate in exciting campus sports activities, athletic competitions and team games. Show your talent, teamwork and sportsmanship.",
                date: "Dates to be announced",
                time: "To be announced",
                venue: "College Sports Ground"
            },

            ai: {
                category: "Workshop",
                title: "AI & Machine Learning Workshop",
                description: "Explore Artificial Intelligence and Machine Learning through practical demonstrations, interactive sessions and hands-on learning activities.",
                date: "Dates to be announced",
                time: "To be announced",
                venue: "Computer Lab"
            }

        };


        const eventModal = document.getElementById("eventModal");


        function openEvent(eventKey) {

            const event = eventDetails[eventKey];

            if (!event) return;

            document.getElementById("modalCategory").textContent = event.category;

            document.getElementById("modalTitle").textContent = event.title;

            document.getElementById("modalDescription").textContent = event.description;

            document.getElementById("modalDate").textContent = event.date;

            document.getElementById("modalTime").textContent = event.time;

            document.getElementById("modalVenue").textContent = event.venue;

            eventModal.classList.add("show");

            document.body.classList.add("modal-open");

        }


        function closeEvent() {

            eventModal.classList.remove("show");

            document.body.classList.remove("modal-open");

        }


        /* Close modal with Escape key */

        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {
                closeEvent();
            }

        });


        /* INITIALIZE EVENT COUNT */

        filterEvents();

        lucide.createIcons();

    </script>
    <?php include 'footer.php'; ?>

</body>

</html>