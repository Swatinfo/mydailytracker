<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoutineCategory;
use App\Models\RoutineTask;
use App\Models\Book;

class RoutineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data (optional - comment out if you want to keep existing data)
        // TaskCompletion::truncate();
        // RoutineTask::truncate();
        // RoutineCategory::truncate();
        // Book::truncate();

        $this->command->info('🔄 Starting CEO Routine Seeder...');

        // Create Routine Categories
        $this->command->info('📁 Creating routine categories...');
        $categories = $this->createRoutineCategories();
        $this->command->info('✅ Created ' . count($categories) . ' categories');

        // Create Routine Tasks for each category
        $this->command->info('📋 Creating routine tasks...');
        $taskCount = $this->createRoutineTasks($categories);
        $this->command->info('✅ Created ' . $taskCount . ' tasks');

        // Create Sample Books
        $this->command->info('📚 Creating sample books...');
        $bookCount = $this->createSampleBooks();
        $this->command->info('✅ Created ' . $bookCount . ' books');

        $this->command->info('🎉 CEO Routine Seeder completed successfully!');
        $this->command->line('');
        $this->command->line('📊 Summary:');
        $this->command->line('  • Categories: ' . count($categories));
        $this->command->line('  • Tasks: ' . $taskCount);
        $this->command->line('  • Books: ' . $bookCount);
        $this->command->line('');
        $this->command->line('🚀 Next steps:');
        $this->command->line('  1. Visit /routine to see your tasks');
        $this->command->line('  2. Visit /routine/debug to troubleshoot if no tasks appear');
        $this->command->line('  3. Visit /dashboard to see your overview');
    }

    /**
     * Create routine categories.
     */
    private function createRoutineCategories(): array
    {
        $categoriesData = [
            [
                'name' => 'Architecture & Technical Design',
                'slug' => 'architecture-technical',
                'description' => 'System architecture, technical design, code reviews, and technical leadership tasks',
                'color' => '#3b82f6', // Blue
                'icon' => 'fas fa-code',
                'sort_order' => 1,
            ],
            [
                'name' => 'Client Projects & Delivery',
                'slug' => 'client-projects',
                'description' => 'Client meetings, project management, delivery coordination, and stakeholder communication',
                'color' => '#10b981', // Green
                'icon' => 'fas fa-handshake',
                'sort_order' => 2,
            ],
            [
                'name' => 'R&D & Innovation',
                'slug' => 'rnd-innovation',
                'description' => 'Research, innovation projects, technology exploration, and future planning',
                'color' => '#f59e0b', // Yellow
                'icon' => 'fas fa-lightbulb',
                'sort_order' => 3,
            ],
            [
                'name' => 'Personal Development',
                'slug' => 'personal-development',
                'description' => 'Learning, reading, skill development, and personal growth activities',
                'color' => '#8b5cf6', // Purple
                'icon' => 'fas fa-graduation-cap',
                'sort_order' => 4,
            ],
            [
                'name' => 'Family & Personal Time',
                'slug' => 'family-personal',
                'description' => 'Family time, personal activities, health, and life balance',
                'color' => '#ef4444', // Red
                'icon' => 'fas fa-heart',
                'sort_order' => 5,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $categoryData) {
            $categories[$categoryData['slug']] = RoutineCategory::create($categoryData);
        }

        return $categories;
    }

    /**
     * Create routine tasks for each category.
     */
    private function createRoutineTasks(array $categories): int
    {
        $totalTasks = 0;

        // Architecture & Technical Design Tasks
        $totalTasks += $this->createArchitectureTasks($categories['architecture-technical']);

        // Client Projects & Delivery Tasks
        $totalTasks += $this->createClientProjectTasks($categories['client-projects']);

        // R&D & Innovation Tasks
        $totalTasks += $this->createRnDTasks($categories['rnd-innovation']);

        // Personal Development Tasks
        $totalTasks += $this->createPersonalDevelopmentTasks($categories['personal-development']);

        // Family & Personal Time Tasks
        $totalTasks += $this->createFamilyPersonalTasks($categories['family-personal']);

        return $totalTasks;
    }

    /**
     * Create Architecture & Technical Design tasks.
     */
    private function createArchitectureTasks(RoutineCategory $category): int
    {
        $tasks = [
            [
                'title' => 'Daily Architecture Review',
                'description' => 'Review system architecture decisions, technical debt, and design patterns',
                'start_time' => '09:30',
                'end_time' => '10:30',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5], // Monday to Friday
                'success_criteria' => 'All architectural decisions documented and reviewed',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Code Review & Technical Leadership',
                'description' => 'Review code submissions, provide technical guidance, and mentor team',
                'start_time' => '10:30',
                'end_time' => '11:30',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5],
                'success_criteria' => 'All code reviews completed with constructive feedback',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Technical Debt Planning',
                'description' => 'Identify, prioritize, and plan technical debt reduction activities',
                'start_time' => '16:00',
                'end_time' => '17:00',
                'estimated_duration' => 60,
                'priority' => 'medium',
                'days_of_week' => [1, 3, 5], // Monday, Wednesday, Friday
                'success_criteria' => 'Technical debt backlog updated and prioritized',
                'target_quality_score' => 7,
            ],
            [
                'title' => 'Technology Stack Assessment',
                'description' => 'Evaluate current technology stack and explore new tools/frameworks',
                'start_time' => '15:00',
                'end_time' => '16:00',
                'estimated_duration' => 60,
                'priority' => 'medium',
                'days_of_week' => [2, 4], // Tuesday, Thursday
                'success_criteria' => 'Technology assessment completed with recommendations',
                'target_quality_score' => 7,
            ],
        ];

        return $this->createTasksForCategory($category, $tasks);
    }

    /**
     * Create Client Projects & Delivery tasks.
     */
    private function createClientProjectTasks(RoutineCategory $category): int
    {
        $tasks = [
            [
                'title' => 'Client Status Meetings',
                'description' => 'Daily standup with clients, project status updates, and issue resolution',
                'start_time' => '11:30',
                'end_time' => '12:30',
                'estimated_duration' => 60,
                'priority' => 'critical',
                'days_of_week' => [1, 2, 3, 4, 5],
                'success_criteria' => 'All client concerns addressed and status communicated',
                'target_quality_score' => 9,
            ],
            [
                'title' => 'Project Planning & Coordination',
                'description' => 'Plan sprints, coordinate with teams, and manage project timelines',
                'start_time' => '12:30',
                'end_time' => '14:00',
                'estimated_duration' => 90,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5],
                'success_criteria' => 'All projects on track with clear next steps',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Delivery Quality Assurance',
                'description' => 'Review deliverables, ensure quality standards, and approve releases',
                'start_time' => '15:30',
                'end_time' => '16:30',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5],
                'success_criteria' => 'All deliverables meet quality standards',
                'target_quality_score' => 9,
            ],
            [
                'title' => 'Client Relationship Management',
                'description' => 'Build relationships, understand needs, and explore new opportunities',
                'start_time' => '16:30',
                'end_time' => '17:30',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [1, 3, 5],
                'success_criteria' => 'Strong client relationships maintained',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Weekly Client Review',
                'description' => 'Comprehensive weekly review of all client projects and performance',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'estimated_duration' => 120,
                'priority' => 'critical',
                'days_of_week' => [1], // Monday
                'success_criteria' => 'Complete understanding of all client project status',
                'target_quality_score' => 9,
            ],
        ];

        return $this->createTasksForCategory($category, $tasks);
    }

    /**
     * Create R&D & Innovation tasks.
     */
    private function createRnDTasks(RoutineCategory $category): int
    {
        $tasks = [
            [
                'title' => 'Innovation Research',
                'description' => 'Research emerging technologies, trends, and innovation opportunities',
                'start_time' => '17:30',
                'end_time' => '18:30',
                'estimated_duration' => 60,
                'priority' => 'medium',
                'days_of_week' => [1, 2, 3, 4, 5],
                'success_criteria' => 'New opportunities identified and documented',
                'target_quality_score' => 7,
            ],
            [
                'title' => 'Prototype Development',
                'description' => 'Build and test prototypes for new ideas and technologies',
                'start_time' => '18:30',
                'end_time' => '19:00',
                'estimated_duration' => 30,
                'priority' => 'medium',
                'days_of_week' => [2, 4], // Tuesday, Thursday
                'success_criteria' => 'Prototype progress made with learnings documented',
                'target_quality_score' => 7,
            ],
            [
                'title' => 'Technology Trend Analysis',
                'description' => 'Analyze market trends, competitive landscape, and technology evolution',
                'start_time' => '09:30',
                'end_time' => '10:30',
                'estimated_duration' => 60,
                'priority' => 'medium',
                'days_of_week' => [6], // Saturday
                'success_criteria' => 'Market analysis completed with insights',
                'target_quality_score' => 7,
            ],
            [
                'title' => 'Innovation Strategy Planning',
                'description' => 'Plan innovation initiatives and strategic technology investments',
                'start_time' => '10:30',
                'end_time' => '11:30',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [6], // Saturday
                'success_criteria' => 'Innovation roadmap updated',
                'target_quality_score' => 8,
            ],
        ];

        return $this->createTasksForCategory($category, $tasks);
    }

    /**
     * Create Personal Development tasks.
     */
    private function createPersonalDevelopmentTasks(RoutineCategory $category): int
    {
        $tasks = [
            [
                'title' => 'CEO Reading Session',
                'description' => 'Dedicated 30-minute reading session for business and leadership books',
                'start_time' => '14:00',
                'end_time' => '14:30',
                'estimated_duration' => 30,
                'priority' => 'critical',
                'days_of_week' => [1, 2, 3, 4, 5, 6], // Monday to Saturday
                'success_criteria' => 'Read at least 10-15 pages with key insights noted',
                'target_quality_score' => 9,
                'is_flexible' => false,
            ],
            [
                'title' => 'Power Nap & Recovery',
                'description' => 'Strategic 30-minute power nap for energy restoration',
                'start_time' => '14:30',
                'end_time' => '15:00',
                'estimated_duration' => 30,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5, 6],
                'success_criteria' => 'Feeling refreshed and energized for afternoon work',
                'target_quality_score' => 8,
                'is_flexible' => false,
            ],
            [
                'title' => 'Skill Development',
                'description' => 'Learn new skills, take online courses, or practice existing skills',
                'start_time' => '18:30',
                'end_time' => '19:00',
                'estimated_duration' => 30,
                'priority' => 'medium',
                'days_of_week' => [1, 3, 5], // Monday, Wednesday, Friday
                'success_criteria' => 'Meaningful progress made in skill development',
                'target_quality_score' => 7,
            ],
            [
                'title' => 'Weekly Reflection & Planning',
                'description' => 'Reflect on week performance and plan improvements for next week',
                'start_time' => '18:00',
                'end_time' => '19:00',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [0], // Sunday
                'success_criteria' => 'Week reviewed and next week planned',
                'target_quality_score' => 8,
            ],
        ];

        return $this->createTasksForCategory($category, $tasks);
    }

    /**
     * Create Family & Personal Time tasks.
     */
    private function createFamilyPersonalTasks(RoutineCategory $category): int
    {
        $tasks = [
            [
                'title' => 'Morning Exercise & Health',
                'description' => 'Morning workout, stretching, or health-focused activities',
                'start_time' => '06:00',
                'end_time' => '07:30',
                'estimated_duration' => 90,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5, 6], // Monday to Saturday
                'success_criteria' => 'Physical exercise completed and feeling energized',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Morning Preparation',
                'description' => 'Personal preparation, breakfast, and day setup',
                'start_time' => '07:30',
                'end_time' => '09:30',
                'estimated_duration' => 120,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5, 6, 0], // Every day
                'success_criteria' => 'Well prepared and ready for productive day',
                'target_quality_score' => 8,
            ],
            [
                'title' => 'Family Dinner Time',
                'description' => 'Dedicated family dinner time without devices - connection and conversation',
                'start_time' => '20:00',
                'end_time' => '21:00',
                'estimated_duration' => 60,
                'priority' => 'critical',
                'days_of_week' => [1, 2, 3, 4, 5, 6, 0], // Every day
                'success_criteria' => 'Quality family time with meaningful conversation',
                'target_quality_score' => 9,
                'is_flexible' => false,
            ],
            [
                'title' => 'Evening Family Time',
                'description' => 'Quality time with family, activities, and relaxation',
                'start_time' => '21:00',
                'end_time' => '22:00',
                'estimated_duration' => 60,
                'priority' => 'high',
                'days_of_week' => [1, 2, 3, 4, 5, 6, 0],
                'success_criteria' => 'Strong family bonds maintained',
                'target_quality_score' => 9,
            ],
            [
                'title' => 'Business Partner Strategy Session',
                'description' => 'Strategic discussion with business partner on company direction',
                'start_time' => '16:00',
                'end_time' => '19:00',
                'estimated_duration' => 180,
                'priority' => 'critical',
                'days_of_week' => [6], // Saturday
                'success_criteria' => 'Strategic alignment achieved with clear action items',
                'target_quality_score' => 9,
            ],
            [
                'title' => 'Weekend Family Activities',
                'description' => 'Special family activities, outings, or quality time',
                'start_time' => '10:00',
                'end_time' => '18:00',
                'estimated_duration' => 480,
                'priority' => 'high',
                'days_of_week' => [0], // Sunday
                'success_criteria' => 'Memorable family experiences created',
                'target_quality_score' => 9,
            ],
        ];

        return $this->createTasksForCategory($category, $tasks);
    }

    /**
     * Helper method to create tasks for a category.
     */
    private function createTasksForCategory(RoutineCategory $category, array $tasks): int
    {
        foreach ($tasks as $index => $taskData) {
            RoutineTask::create([
                'routine_category_id' => $category->id,
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'start_time' => $taskData['start_time'],
                'end_time' => $taskData['end_time'],
                'estimated_duration' => $taskData['estimated_duration'],
                'priority' => $taskData['priority'],
                'days_of_week' => $taskData['days_of_week'],
                'is_flexible' => $taskData['is_flexible'] ?? true,
                'target_quality_score' => $taskData['target_quality_score'],
                'success_criteria' => $taskData['success_criteria'],
                'notes' => $taskData['notes'] ?? null,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        return count($tasks);
    }

    /**
     * Create sample books for the reading library.
     */
    private function createSampleBooks(): int
    {
        $books = [
            // Business & Leadership Books
            [
                'title' => 'Good to Great',
                'author' => 'Jim Collins',
                'category' => 'business',
                'total_pages' => 320,
                'description' => 'Why some companies make the leap and others don\'t. A comprehensive study of companies that achieved sustained excellence.',
                'priority' => 5,
                'status' => 'want_to_read',
                'format' => 'physical',
                'tags' => ['leadership', 'business strategy', 'corporate excellence'],
            ],
            [
                'title' => 'The Lean Startup',
                'author' => 'Eric Ries',
                'category' => 'business',
                'total_pages' => 336,
                'description' => 'How today\'s entrepreneurs use continuous innovation to create radically successful businesses.',
                'priority' => 4,
                'status' => 'currently_reading',
                'current_page' => 120,
                'started_date' => now()->subDays(10),
                'format' => 'ebook',
                'tags' => ['entrepreneurship', 'innovation', 'startup'],
            ],
            [
                'title' => 'Zero to One',
                'author' => 'Peter Thiel',
                'category' => 'business',
                'total_pages' => 224,
                'description' => 'Notes on startups, or how to build the future.',
                'priority' => 4,
                'status' => 'completed',
                'current_page' => 224,
                'started_date' => now()->subDays(30),
                'completed_date' => now()->subDays(5),
                'rating' => 9,
                'review' => 'Excellent insights on building monopolistic businesses and thinking differently about innovation.',
                'format' => 'physical',
                'key_insights' => [
                    'Focus on creating monopolies, not competing in existing markets',
                    'Technology should be 10x better, not just incrementally better',
                    'Start with a small market and dominate it completely'
                ],
                'action_items' => [
                    'Evaluate current products for 10x improvement opportunities',
                    'Identify potential monopolistic advantages in our market',
                    'Focus on unique value propositions rather than competition'
                ],
                'tags' => ['innovation', 'monopoly', 'technology'],
            ],

            // Technical Books
            [
                'title' => 'Clean Architecture',
                'author' => 'Robert C. Martin',
                'category' => 'technical',
                'total_pages' => 432,
                'description' => 'A craftsman\'s guide to software structure and design.',
                'priority' => 5,
                'status' => 'currently_reading',
                'current_page' => 200,
                'started_date' => now()->subDays(15),
                'format' => 'physical',
                'tags' => ['architecture', 'software design', 'clean code'],
            ],
            [
                'title' => 'Designing Data-Intensive Applications',
                'author' => 'Martin Kleppmann',
                'category' => 'technical',
                'total_pages' => 560,
                'description' => 'The big ideas behind reliable, scalable, and maintainable systems.',
                'priority' => 4,
                'status' => 'want_to_read',
                'format' => 'ebook',
                'tags' => ['databases', 'scalability', 'distributed systems'],
            ],

            // Personal Development
            [
                'title' => 'Atomic Habits',
                'author' => 'James Clear',
                'category' => 'personal_development',
                'total_pages' => 320,
                'description' => 'An easy and proven way to build good habits and break bad ones.',
                'priority' => 5,
                'status' => 'completed',
                'current_page' => 320,
                'started_date' => now()->subDays(45),
                'completed_date' => now()->subDays(20),
                'rating' => 10,
                'review' => 'Life-changing approach to building systems and habits. Practical and immediately applicable.',
                'format' => 'audiobook',
                'key_insights' => [
                    'Focus on systems, not goals',
                    'Make good habits obvious, attractive, easy, and satisfying',
                    'Small improvements compound over time'
                ],
                'action_items' => [
                    'Implement habit stacking for new routines',
                    'Create environment designs that promote good habits',
                    'Track habits consistently for accountability'
                ],
                'tags' => ['habits', 'productivity', 'self-improvement'],
            ],
            [
                'title' => 'Deep Work',
                'author' => 'Cal Newport',
                'category' => 'personal_development',
                'total_pages' => 304,
                'description' => 'Rules for focused success in a distracted world.',
                'priority' => 4,
                'status' => 'want_to_read',
                'format' => 'physical',
                'tags' => ['focus', 'productivity', 'attention'],
            ],

            // Leadership
            [
                'title' => 'The Five Dysfunctions of a Team',
                'author' => 'Patrick Lencioni',
                'category' => 'leadership',
                'total_pages' => 240,
                'description' => 'A leadership fable about overcoming the five dysfunctions that plague teams.',
                'priority' => 4,
                'status' => 'completed',
                'current_page' => 240,
                'started_date' => now()->subDays(60),
                'completed_date' => now()->subDays(35),
                'rating' => 8,
                'review' => 'Great framework for understanding and addressing team challenges.',
                'format' => 'physical',
                'key_insights' => [
                    'Trust is the foundation of effective teamwork',
                    'Healthy conflict leads to better decisions',
                    'Commitment requires buy-in from all team members'
                ],
                'tags' => ['team building', 'leadership', 'organizational behavior'],
            ],

            // Strategy
            [
                'title' => 'Blue Ocean Strategy',
                'author' => 'W. Chan Kim',
                'category' => 'strategy',
                'total_pages' => 272,
                'description' => 'How to create uncontested market space and make competition irrelevant.',
                'priority' => 3,
                'status' => 'paused',
                'current_page' => 100,
                'started_date' => now()->subDays(40),
                'format' => 'ebook',
                'tags' => ['strategy', 'innovation', 'market creation'],
            ],

            // Biography
            [
                'title' => 'Steve Jobs',
                'author' => 'Walter Isaacson',
                'category' => 'biography',
                'total_pages' => 656,
                'description' => 'The exclusive biography of Apple\'s co-founder and CEO.',
                'priority' => 3,
                'status' => 'want_to_read',
                'format' => 'physical',
                'tags' => ['biography', 'innovation', 'technology'],
            ],
        ];

        foreach ($books as $bookData) {
            Book::create($bookData);
        }

        return count($books);
    }
}