
<?php
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Kolkata');

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function categoryClass($category) {
    return match (strtolower($category)) {
        'technical' => 'cover-tech',
        'sports' => 'cover-sports',
        default => 'cover-workshop'
    };
}

function categoryIcon($category) {
    return match (strtolower($category)) {
        'technical' => 'code-2',
        'cultural' => 'music',
        'sports' => 'trophy',
        'workshop' => 'lightbulb',
        default => 'calendar-days'
    };
}

function displayCategory($category) {
    return match (strtolower($category)) {
        'technical' => 'Technical',
        'cultural' => 'Cultural',
        'sports' => 'Sports',
        'workshop' => 'Workshop',
        default => 'Other'
    };
}

function renderEventCard($event) {
    $category = $event['category'];
    $class = categoryClass($category);
    $icon = categoryIcon($category);

    $start = strtotime($event['start_datetime']);
    $end = strtotime($event['end_datetime']);

    $image = trim((string)($event['event_image'] ?? ''));

    $month = date('M', $start);
    $day = date('d', $start);
    $year = date('Y', $start);

    $date = date('F j, Y', $start);
    $time = date('g:i A', $start) . ' - ' . date('g:i A', $end);


    ?>
    <article class="event-card"
        data-category="<?= e(strtolower($category)) ?>"
        data-title="<?= e($event['event_title']) ?>"
        data-description="<?= e($event['event_description']) ?>"
        data-date="<?= e($date) ?>"
        data-time="<?= e($time) ?>"
        data-venue="<?= e($event['venue']) ?>">

        <div class="event-cover <?= e($class) ?>">

            <?php if ($image !== ''): ?>
                <img
                    src="<?= e($image) ?>"
                    alt="<?= e($event['event_title']) ?>"
                    class="event-poster-image"
                >
            <?php endif; ?>

            <span class="cover-label"><?= e(displayCategory($category)) ?></span>

            <div class="cover-graphic">
                <i data-lucide="<?= e($icon) ?>"></i>
            </div>

            <div class="cover-title">
                <span><?= e($year) ?> CAMPUS EVENT</span>
                <h3><?= e($event['event_title']) ?></h3>
            </div>

            <span class="cover-year"><?= e($year) ?></span>

        </div>

        <div class="event-card-body">
            <div class="event-date">
                <span class="date-month"><?= e($month) ?></span>
                <span class="date-day"><?= e($day) ?></span>
            </div>

            <div class="event-card-content">
                <h3><?= e($event['event_title']) ?></h3>

                <p><?= e($event['event_description']) ?></p>

                <div class="event-card-meta">
                    <span>
                        <i data-lucide="clock"></i>
                        <?= e(date('g:i A', $start)) ?>
                    </span>

                    <span>
                        <i data-lucide="map-pin"></i>
                        <?= e($event['venue']) ?>
                    </span>
                </div>
            </div>

            <button class="card-arrow"
                onclick="openEvent(this.closest('.event-card'))"
                aria-label="View <?= e($event['event_title']) ?> details">
                <i data-lucide="arrow-up-right"></i>
            </button>
        </div>
    </article>
    <?php
}

try {
    // Featured event
    $featuredQuery = $pdo->prepare("
        SELECT *
        FROM events
        WHERE is_featured = 1
          AND end_datetime >= NOW()
        ORDER BY start_datetime ASC
        LIMIT 1
    ");
    $featuredQuery->execute();
    $featuredEvent = $featuredQuery->fetch(PDO::FETCH_ASSOC);

    // Upcoming and currently running events
    if ($featuredEvent) {
        $upcomingQuery = $pdo->prepare("
            SELECT *
            FROM events
            WHERE end_datetime >= NOW()
              AND event_id != :featured_id
            ORDER BY start_datetime ASC
        ");

        $upcomingQuery->execute([
            'featured_id' => $featuredEvent['event_id']
        ]);
    } else {
        $upcomingQuery = $pdo->prepare("
            SELECT *
            FROM events
            WHERE end_datetime >= NOW()
            ORDER BY start_datetime ASC
        ");

        $upcomingQuery->execute();
    }

    $upcomingEvents = $upcomingQuery->fetchAll(PDO::FETCH_ASSOC);

    // Past events
    $pastQuery = $pdo->prepare("
        SELECT *
        FROM events
        WHERE end_datetime < NOW()
        ORDER BY end_datetime DESC
    ");

    $pastQuery->execute();
    $pastEvents = $pastQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {
    error_log('Events page database error: ' . $exception->getMessage());

    $featuredEvent = false;
    $upcomingEvents = [];
    $pastEvents = [];
}
?>

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

            <?php if ($featuredEvent): ?>

                <?php
                $featuredStart = strtotime($featuredEvent['start_datetime']);
                $featuredEnd = strtotime($featuredEvent['end_datetime']);
                $featuredImage = trim((string)($featuredEvent['event_image'] ?? ''));
                ?>

                <div class="featured-card"
                    data-category="<?= e(displayCategory($featuredEvent['category'])) ?>"
                    data-title="<?= e($featuredEvent['event_title']) ?>"
                    data-description="<?= e($featuredEvent['event_description']) ?>"
                    data-date="<?= e(date('F j, Y', $featuredStart)) ?>"
                    data-time="<?= e(date('g:i A', $featuredStart) . ' - ' . date('g:i A', $featuredEnd)) ?>"
                    data-venue="<?= e($featuredEvent['venue']) ?>">

                    <div class="featured-art">

                        <?php if ($featuredImage !== ''): ?>

                            <img
                                src="<?= e($featuredImage) ?>"
                                alt="<?= e($featuredEvent['event_title']) ?>"
                                class="featured-event-image"
                            >

                        <?php else: ?>

                            <div class="featured-image-placeholder">
                                <i data-lucide="calendar-days"></i>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="featured-info">

                        <div class="event-category celebration">
                            <i data-lucide="calendar-days"></i>
                            <span><?= e(displayCategory($featuredEvent['category'])) ?></span>
                        </div>

                        <h3><?= e($featuredEvent['event_title']) ?></h3>

                        <p><?= e($featuredEvent['event_description']) ?></p>

                        <div class="featured-meta">

                            <div>
                                <i data-lucide="calendar-days"></i>
                                <span><?= e(date('F j, Y', $featuredStart)) ?></span>
                            </div>

                            <div>
                                <i data-lucide="clock"></i>
                                <span>
                                    <?= e(date('g:i A', $featuredStart)) ?>
                                    -
                                    <?= e(date('g:i A', $featuredEnd)) ?>
                                </span>
                            </div>

                            <div>
                                <i data-lucide="map-pin"></i>
                                <span><?= e($featuredEvent['venue']) ?></span>
                            </div>

                        </div>

                        <button class="details-button"
                            onclick="openEvent(this.closest('.featured-card'))">
                            Explore Event
                            <i data-lucide="arrow-up-right"></i>
                        </button>

                    </div>
                </div>

            <?php else: ?>

                <div class="no-events" style="display:block;">
                    <i data-lucide="calendar-x"></i>
                    <h3>No Featured Event</h3>
                    <p>There is currently no featured event. Check back later for updates.</p>
                </div>

            <?php endif; ?>

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
                    <?= count($upcomingEvents) ?>
                    <?= count($upcomingEvents) === 1 ? 'Event' : 'Events' ?>
                </div>

            </div>

            <!-- SEARCH -->
            <div class="event-toolbar">

                <div class="search-box">
                    <i data-lucide="search"></i>

                    <input type="text"
                        id="eventSearch"
                        placeholder="Search events by name..."
                        autocomplete="off">

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

                <button class="filter-btn" data-filter="other">
                    <i data-lucide="calendar-days"></i>
                    Other
                </button>

            </div>

            <!-- DYNAMIC UPCOMING EVENT CARDS -->
            <div class="events-grid" id="eventsGrid">

                <?php if (!empty($upcomingEvents)): ?>

                    <?php foreach ($upcomingEvents as $event): ?>
                        <?php renderEventCard($event); ?>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- NO EVENTS FOUND -->
            <div class="no-events" id="noEvents"
                style="<?= empty($upcomingEvents) ? 'display:block;' : 'display:none;' ?>">

                <i data-lucide="calendar-x"></i>
                <h3>No Events Found</h3>
                <p>Try another category or search for a different event.</p>

            </div>

        </section>


        <!-- DYNAMIC PAST EVENTS -->
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

                <?php if (!empty($pastEvents)): ?>

                    <?php foreach ($pastEvents as $event): ?>
                        <?php renderEventCard($event); ?>
                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="no-events" style="display:block;">
                        <i data-lucide="calendar-x"></i>
                        <h3>No Past Events</h3>
                        <p>Past events will appear here after they have ended.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- CAMPUS MEMORIES -->
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

            <button class="modal-close"
                onclick="closeEvent()"
                aria-label="Close event details">
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
    lucide.createIcons();

    /* SEARCH AND FILTER */

    const searchInput = document.getElementById("eventSearch");
    const clearSearch = document.getElementById("clearSearch");
    const filterButtons = document.querySelectorAll(".filter-btn");

    const eventCards = document.querySelectorAll("#eventsGrid .event-card");

    const eventCount = document.getElementById("eventCount");
    const noEvents = document.getElementById("noEvents");

    let activeFilter = "all";

    function filterEvents() {

        const searchText = searchInput.value.toLowerCase().trim();

        let visibleCount = 0;

        eventCards.forEach(card => {

            const category = (card.dataset.category || "").toLowerCase();
            const title = (card.dataset.title || "").toLowerCase();
            const cardText = card.innerText.toLowerCase();

            const matchesCategory =
                activeFilter === "all" || category === activeFilter;

            const matchesSearch =
                title.includes(searchText) || cardText.includes(searchText);

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


    /* EVENT DETAILS MODAL */

    const eventModal = document.getElementById("eventModal");

    function openEvent(element) {

        if (!element) return;

        const event = element.dataset;

        document.getElementById("modalCategory").textContent =
            event.category || "EVENT";

        document.getElementById("modalTitle").textContent =
            event.title || "";

        document.getElementById("modalDescription").textContent =
            event.description || "";

        document.getElementById("modalDate").textContent =
            event.date || "";

        document.getElementById("modalTime").textContent =
            event.time || "";

        document.getElementById("modalVenue").textContent =
            event.venue || "";

        eventModal.classList.add("show");
        document.body.classList.add("modal-open");

    }

    function closeEvent() {

        eventModal.classList.remove("show");
        document.body.classList.remove("modal-open");

    }

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {
            closeEvent();
        }

    });


    /* INITIALIZE */

    filterEvents();
    lucide.createIcons();

</script>

<?php include 'footer.php'; ?>

</body>
</html>