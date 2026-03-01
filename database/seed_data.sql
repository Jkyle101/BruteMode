USE brutemode;
INSERT INTO exercises(name,muscle_group,is_custom) VALUES
('Bench Press','Chest',0),('Incline Bench','Chest',0),('Push-up','Chest',0),
('Squat','Legs',0),('Front Squat','Legs',0),
('Deadlift','Back',0),('Romanian Deadlift','Back',0),('Lat Pulldown','Back',0),('Bent Row','Back',0),
('Overhead Press','Shoulders',0),('Dumbbell Press','Shoulders',0),
('Bicep Curl','Arms',0),('Tricep Extension','Arms',0),
('Lunge','Legs',0),('Leg Press','Legs',0),('Leg Curl','Legs',0),('Calf Raise','Legs',0),
('Hip Thrust','Glutes',0),('Pull-up','Back',0),('Chin-up','Arms',0),('Plank','Core',0),('Crunch','Core',0);
INSERT INTO badges(code,name,tier,icon,description) VALUES
('beginner_first','Beginner: First workout','Beginner','🥉','First workout'),
('beginner_5','Beginner: 5 workouts','Beginner','🥉','5 workouts'),
('beginner_streak3','Beginner: 3-day streak','Beginner','🥉','3-day streak'),
('intermediate_20','Intermediate: 20 workouts','Intermediate','🥈','20 workouts'),
('intermediate_streak7','Intermediate: 7-day streak','Intermediate','🥈','7-day streak'),
('intermediate_pr5','Intermediate: 5 PR improvements','Intermediate','🥈','5 PR improvements'),
('advanced_50','Advanced: 50 workouts','Advanced','🥇','50 workouts'),
('advanced_streak30','Advanced: 30-day streak','Advanced','🥇','30-day streak'),
('advanced_volume','Advanced: Volume milestones','Advanced','🥇','Volume milestones'),
('elite_100','Elite: 100 workouts','Elite','🔥','100 workouts'),
('elite_streak60','Elite: 60-day streak','Elite','🔥','60-day streak'),
('elite_year','Elite: 1-year consistency','Elite','🔥','1-year consistency');
