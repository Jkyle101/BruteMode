# BruteMode Release Notes

All notable changes to this project will be documented in this file.

---

## [1.0.0] - 2024-01-15

### Added
- **User Authentication System**
  - User registration with email, password, name, weight, and fitness goals
  - Login/logout functionality
  - Profile management with picture upload
  - Password change functionality

- **Workout Tracking**
  - Create, edit, and delete workouts
  - Add exercises to workouts
  - Log sets with reps and weight
  - Automatic 1RM (One Rep Max) calculation formula
  - Total workout volume tracking
  - Duplicate previous workouts feature

- **Exercise Library**
  - Pre-seeded library with 23 common exercises
  - Custom exercise creation
  - Muscle group categorization (Chest, Back, Legs, Shoulders, Arms, Core, Glutes)
  - Favorite exercises marking
  - Search and filter functionality

- **Body Tracking**
  - Body measurements logging (weight, neck, chest, waist, hips, arm, thigh)
  - Progress photo uploads
  - Interactive weight progress chart

- **Habit Tracking**
  - Daily water intake tracking (ml)
  - Sleep hours logging
  - Protein consumption tracking (g)
  - Weekly frequency rank system

- **Points & Ranking System**
  - Points earned for:
    - Completing workouts: +10 points
    - Setting personal records: +5 points
    - Logging daily habits: +1 point
  - Rank progression system:
    - Recruit (0-99 points)
    - Warrior (100-299 points)
    - Gladiator (300-599 points)
    - Dominator (600-999 points)
    - Warlord (1000-1499 points)
    - Titan (1500+ points)
    - Legend (future)

- **Achievements & Badges**
  - 12 unlockable badges across 4 tiers:
    - Beginner: First workout, 5 workouts, 3-day streak
    - Intermediate: 20 workouts, 7-day streak, 5 PR improvements
    - Advanced: 50 workouts, 30-day streak, Volume milestones (50,000+)
    - Elite: 100 workouts, 60-day streak, 1-year consistency (260+ workouts)
  - Badge unlock notifications
  - Badge animation effects

- **Data Visualization**
  - Weekly workout summary bar chart
  - Weight progress line chart
  - Rank progress bar
  - Lifetime volume tracking

- **Export Feature**
  - Printable workout history export
  - PDF-ready layout

### Technical
- Automatic database creation on first run
- Auto-seeding of exercises and badges
- Dark theme UI with Bootstrap 5
- Responsive design
- MySQLi database connectivity
- Session-based authentication
- File upload handling for profile pictures and progress photos

---

## [Upcoming Features]

### Planned for Future Releases
- [ ] Coach/training partner system
- [ ] Social features (workout sharing)
- [ ] Mobile app integration
- [ ] Import/export data (JSON/CSV)
- [ ] Workout templates
- [ ] Rest timer between sets
- [ ] Exercise descriptions and tutorials
- [ ] Meal/nutrition tracking
- [ ] Goal setting and tracking
- [ ] Workout reminders/notifications
- [ ] Advanced analytics dashboard

---

## Version History

| Version | Date | Status |
|---------|------|--------|
| 1.0.0 | 2024-01-15 | Initial Release |

---

## Known Issues

- No known critical issues at release
- Browser print functionality required for PDF export

---

## Support

For issues or feature requests, please contact the development team.
