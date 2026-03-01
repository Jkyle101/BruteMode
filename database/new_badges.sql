-- New badges to add to the database
-- Run this SQL to add more badges

USE brutemode;

INSERT INTO badges(code,name,tier,icon,description) VALUES
/* Volume Milestones */
('volume_1k','Volume: 1K Club','Bronze','💪','Lift 1,000 kg total'),
('volume_5k','Volume: 5K Club','Bronze','💪','Lift 5,000 kg total'),
('volume_10k','Volume: 10K Club','Silver','💪','Lift 10,000 kg total'),
('volume_25k','Volume: 25K Club','Silver','💪','Lift 25,000 kg total'),
('volume_50k','Volume: 50K Club','Gold','💪','Lift 50,000 kg total'),
('volume_100k','Volume: 100K Club','Gold','💪','Lift 100,000 kg total'),
/* Body Tracking */
('body_first','Body: First Log','Bronze','📊','Log your first body measurement'),
('body_week','Body: Week Tracker','Bronze','📊','Log body measurements for 7 days'),
('body_month','Body: Month Tracker','Silver','📊','Log body measurements for 30 days'),
('body_goal','Body: Goal Reached','Gold','🎯','Reach your fitness goal'),
/* Habit Tracking */
('habit_water','Hydration Hero','Bronze','💧','Track water intake 7 days'),
('habit_sleep','Sleep Master','Bronze','😴','Track sleep 7 days'),
('habit_protein','Protein Champion','Bronze','🥩','Track protein 7 days'),
('habit_trinity','Habit Trinity','Silver','🏆','Track all habits for 14 days'),
('habit_month','Habit Legend','Gold','👑','Track all habits for 30 days'),
/* Special Badges */
('early_bird','Early Bird','Special','🌅','Workout before 7 AM'),
('night_owl','Night Owl','Special','🦉','Workout after 9 PM'),
('weekend_warrior','Weekend Warrior','Special','⚔️','Workout every weekend for a month'),
('weekday_champion','Weekday Champion','Special','🏅','Workout every weekday for a month');
