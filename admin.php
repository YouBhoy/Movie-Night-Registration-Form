<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Optional: Check session timeout (24 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
    session_destroy();
    header('Location: admin-login.php?timeout=1');
    exit;
}

require_once 'config.php';

// Get event settings
try {
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM event_settings");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
    error_log("Settings error: " . $e->getMessage());
}

// Get all registrations
try {
    $stmt = $pdo->query("
        SELECT id, staff_name, number_of_pax, selected_seats, shift_preference, created_at 
        FROM registrations 
        ORDER BY created_at DESC
    ");
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_registrations, 
            SUM(number_of_pax) as total_attendees 
        FROM registrations
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $registrations = [];
    $stats = ['total_registrations' => 0, 'total_attendees' => 0];
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WD Movie Night - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cinema-brown': '#8b4513',
                        'cinema-light': '#f4e4bc',
                        'cinema-gold': '#deb887',
                        'cinema-dark': '#654321',
                        'wd-cyan': '#00d4ff',
                        'wd-blue': '#0066cc'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-800 via-slate-700 to-slate-600 min-h-screen">
    
    <div class="max-w-7xl mx-auto p-5">
        <!-- Header with WD Branding and Logout -->
        <header class="text-center mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <img src="images/wd-logo.png" alt="Western Digital" class="h-16 w-auto">
                    <div class="text-left">
                        <h1 class="text-4xl font-bold text-cinema-light">WD Movie Night Admin</h1>
                        <p class="text-slate-300 text-lg">Administrative Dashboard</p>
                    </div>
                </div>
                
                <!-- User Info & Logout -->
                <div class="flex items-center gap-4">
                    <div class="text-right text-slate-300">
                        <div class="text-sm">Welcome back,</div>
                        <div class="font-semibold text-cinema-light"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                    </div>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Event Settings Card -->
        <div class="bg-cinema-light/95 rounded-2xl shadow-xl mb-8 overflow-hidden">
            <div class="p-6 border-b border-amber-200">
                <h2 class="text-2xl font-semibold text-cinema-brown flex items-center gap-2">
                    <i class="fas fa-cog"></i> Event Settings
                </h2>
                <p class="text-amber-700">Configure your movie night event details</p>
            </div>
            <div class="p-6">
                <form id="settingsForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="event_title" class="block text-sm font-semibold text-cinema-brown mb-2">Event Title</label>
                        <input type="text" id="event_title" name="event_title" 
                               value="<?php echo htmlspecialchars($settings['event_title'] ?? ''); ?>" 
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all" required>
                    </div>

                    <div>
                        <label for="movie_title" class="block text-sm font-semibold text-cinema-brown mb-2">Movie/Event Name</label>
                        <input type="text" id="movie_title" name="movie_title" 
                               value="<?php echo htmlspecialchars($settings['movie_title'] ?? ''); ?>" 
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all" required>
                    </div>

                    <div>
                        <label for="event_date" class="block text-sm font-semibold text-cinema-brown mb-2">Event Date</label>
                        <input type="text" id="event_date" name="event_date" 
                               value="<?php echo htmlspecialchars($settings['event_date'] ?? ''); ?>" 
                               placeholder="e.g., Friday, 16 May '25"
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                    </div>

                    <div>
                        <label for="event_time" class="block text-sm font-semibold text-cinema-brown mb-2">Event Time</label>
                        <input type="text" id="event_time" name="event_time" 
                               value="<?php echo htmlspecialchars($settings['event_time'] ?? ''); ?>" 
                               placeholder="e.g., 8:30 PM"
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                    </div>

                    <div>
                        <label for="venue" class="block text-sm font-semibold text-cinema-brown mb-2">Venue</label>
                        <input type="text" id="venue" name="venue" 
                               value="<?php echo htmlspecialchars($settings['venue'] ?? ''); ?>" 
                               placeholder="e.g., Cinema Hall 1"
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                    </div>

                    <div>
                        <label for="max_attendees_per_registration" class="block text-sm font-semibold text-cinema-brown mb-2">Max Attendees per Registration</label>
                        <select id="max_attendees_per_registration" name="max_attendees_per_registration"
                                class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                            <option value="2" <?php echo ($settings['max_attendees_per_registration'] ?? '') == '2' ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?php echo ($settings['max_attendees_per_registration'] ?? '') == '3' ? 'selected' : ''; ?>>3</option>
                            <option value="4" <?php echo ($settings['max_attendees_per_registration'] ?? '') == '4' ? 'selected' : ''; ?>>4</option>
                            <option value="5" <?php echo ($settings['max_attendees_per_registration'] ?? '') == '5' ? 'selected' : ''; ?>>5</option>
                        </select>
                    </div>

                    <div>
                        <label for="normal_shift_label" class="block text-sm font-semibold text-cinema-brown mb-2">Normal Shift Label</label>
                        <input type="text" id="normal_shift_label" name="normal_shift_label" 
                               value="<?php echo htmlspecialchars($settings['normal_shift_label'] ?? ''); ?>" 
                               placeholder="e.g., Normal Shift"
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                    </div>

                    <div>
                        <label for="crew_shift_label" class="block text-sm font-semibold text-cinema-brown mb-2">Crew Shift Label</label>
                        <input type="text" id="crew_shift_label" name="crew_shift_label" 
                               value="<?php echo htmlspecialchars($settings['crew_shift_label'] ?? ''); ?>" 
                               placeholder="e.g., Crew C - Day Shift"
                               class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label for="event_description" class="block text-sm font-semibold text-cinema-brown mb-2">Event Description</label>
                        <textarea id="event_description" name="event_description" rows="3" 
                                  placeholder="Brief description of the event"
                                  class="w-full p-3 border-2 border-amber-300 rounded-lg bg-white/80 focus:bg-white focus:border-cinema-brown transition-all"><?php echo htmlspecialchars($settings['event_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="bg-gradient-to-r from-cinema-gold to-amber-400 text-cinema-brown py-3 px-6 rounded-lg font-semibold hover:from-amber-400 hover:to-cinema-gold transition-all flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-cinema-light/95 p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-amber-700 font-medium">Total Registrations</span>
                    <i class="fas fa-user-check text-amber-600"></i>
                </div>
                <div class="text-3xl font-bold text-cinema-brown"><?php echo $stats['total_registrations']; ?></div>
            </div>

            <div class="bg-cinema-light/95 p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-amber-700 font-medium">Total Attendees</span>
                    <i class="fas fa-users text-amber-600"></i>
                </div>
                <div class="text-3xl font-bold text-cinema-brown"><?php echo $stats['total_attendees']; ?></div>
            </div>

            <div class="bg-cinema-light/95 p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-amber-700 font-medium">Export Data</span>
                    <i class="fas fa-download text-amber-600"></i>
                </div>
                <button onclick="exportToCSV()" class="bg-gradient-to-r from-cinema-gold to-amber-400 text-cinema-brown py-2 px-4 rounded-lg font-semibold text-sm hover:from-amber-400 hover:to-cinema-gold transition-all">
                    Download CSV
                </button>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="bg-cinema-light/95 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-amber-200">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-cinema-brown">All Registrations</h2>
                        <p class="text-amber-700">Complete list of staff who have registered</p>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="relative max-w-xs">
                        <input type="text" id="searchInput" placeholder="Search by staff name..." 
                               class="w-full pl-4 pr-10 py-2 border-2 border-amber-300 rounded-lg text-sm bg-white/80 focus:bg-white focus:border-cinema-brown transition-all">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-amber-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <?php if (empty($registrations)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-users text-6xl text-amber-300 mb-4"></i>
                        <p class="text-amber-700 text-lg">No registrations yet</p>
                    </div>
                <?php else: ?>
                    <table id="registrationsTable" class="w-full">
                        <thead class="bg-cinema-gold/20">
                            <tr>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Staff Name</th>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Attendees</th>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Shift</th>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Selected Seats</th>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Movie Details</th>
                                <th class="text-left py-4 px-6 font-semibold text-cinema-brown">Registration Date</th>
                                <th class="text-center py-4 px-6 font-semibold text-cinema-brown">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $registration): ?>
                                <tr data-registration-id="<?php echo $registration['id']; ?>" class="border-b border-amber-200 hover:bg-amber-50 transition-colors">
                                    <td class="py-4 px-6 font-medium text-cinema-brown staff-name"><?php echo htmlspecialchars($registration['staff_name']); ?></td>
                                    <td class="py-4 px-6">
                                        <span class="bg-cinema-brown/10 text-cinema-brown px-3 py-1 rounded-full text-sm font-medium">
                                            <?php echo $registration['number_of_pax']; ?> 
                                            <?php echo $registration['number_of_pax'] == 1 ? 'person' : 'people'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $registration['shift_preference'] == 'normal' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $registration['shift_preference'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-mono text-sm text-cinema-brown font-semibold">
                                        <?php echo $registration['selected_seats'] ? htmlspecialchars($registration['selected_seats']) : 'Not selected'; ?>
                                    </td>
                                    <td class="py-4 px-6 min-w-48">
                                        <div class="text-sm">
                                            <div class="font-semibold text-cinema-brown"><?php echo htmlspecialchars($settings['movie_title'] ?? 'Movie Night'); ?></div>
                                            <div class="text-amber-700 text-xs mt-1">
                                                <?php echo htmlspecialchars($settings['event_date'] ?? 'TBA'); ?> | <?php echo htmlspecialchars($settings['event_time'] ?? 'TBA'); ?>
                                            </div>
                                            <div class="text-amber-700 text-xs"><?php echo htmlspecialchars($settings['venue'] ?? 'TBA'); ?></div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-amber-700 text-sm">
                                        <?php echo date('M j, Y g:i A', strtotime($registration['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <button onclick="deleteRegistration(<?php echo $registration['id']; ?>, '<?php echo htmlspecialchars($registration['staff_name']); ?>')" 
                                                class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-all hover:scale-105" 
                                                title="Delete Registration">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="admin-script.js"></script>
</body>
</html>
