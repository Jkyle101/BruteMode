<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();

// Get all badges grouped by tier
$all=fetch_all(q("SELECT * FROM badges ORDER BY 
  CASE 
    WHEN tier='Beginner' THEN 1 
    WHEN tier='Bronze' THEN 2 
    WHEN tier='Intermediate' THEN 3 
    WHEN tier='Silver' THEN 4 
    WHEN tier='Advanced' THEN 5 
    WHEN tier='Gold' THEN 6 
    WHEN tier='Elite' THEN 7 
    WHEN tier='Special' THEN 8 
    ELSE 9 
  END, name"));

$got=fetch_all(q("SELECT b.code FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=?","i",[$uid]));
$have=array_map(function($r){return $r['code'];},$got);

// Group badges by category
$categories = [
  'Workout Milestones' => ['beginner_first','beginner_5','beginner_streak3','intermediate_20','intermediate_streak7','intermediate_pr5','advanced_50','advanced_streak30','advanced_volume10k','elite_100','elite_streak60','elite_year'],
  'Volume King' => ['volume_1k','volume_5k','volume_10k','volume_25k','volume_50k','volume_100k'],
  'Body Tracking' => ['body_first','body_week','body_month','body_goal'],
  'Habit Hero' => ['habit_water','habit_sleep','habit_protein','habit_trinity','habit_month'],
  'Special' => ['early_bird','night_owl','weekend_warrior','weekday_champion']
];

// Count badges
$totalBadges = count($all);
$earnedBadges = count($have);
$progress = $totalBadges > 0 ? round(($earnedBadges / $totalBadges) * 100) : 0;
?>
<div class="container py-4">
  <!-- Header Section -->
  <div class="achievements-header">
    <div class="achievements-title">
      <h2>🏆 Achievements</h2>
      <p>Track your fitness journey milestones</p>
    </div>
    <div class="achievements-progress">
      <div class="progress-ring-container">
        <svg class="progress-ring" width="120" height="120">
          <circle class="progress-bg" cx="60" cy="60" r="52"/>
          <circle class="progress-fill" cx="60" cy="60" r="52" 
            stroke-dasharray="<?php echo 327 * $progress / 100; ?> 327"/>
        </svg>
        <div class="progress-value">
          <span class="value"><?php echo $earnedBadges; ?></span>
          <span class="unit">/ <?php echo $totalBadges; ?></span>
        </div>
      </div>
      <div class="progress-label">Badges Earned</div>
    </div>
  </div>

  <?php foreach($categories as $catName => $catCodes): ?>
    <?php 
    $catBadges = array_filter($all, function($b) use ($catCodes) {
      return in_array($b['code'], $catCodes);
    });
    if(empty($catBadges)) continue;
    $catEarned = count(array_filter($catBadges, function($b) use ($have) {
      return in_array($b['code'], $have);
    }));
    ?>
    <div class="badge-category">
      <div class="category-header">
        <h3><?php echo $catName; ?></h3>
        <span class="category-count"><?php echo $catEarned; ?>/<?php echo count($catBadges); ?></span>
      </div>
      <div class="badge-grid">
        <?php foreach($catBadges as $b): 
          $unlocked = in_array($b['code'], $have);
          $tierClass = strtolower($b['tier']);
        ?>
          <div class="badge-card-new <?php echo $unlocked ? 'unlocked' : 'locked'; ?> tier-<?php echo $tierClass; ?>">
            <div class="badge-icon"><?php echo sanitize($b['icon']); ?></div>
            <div class="badge-info">
              <div class="badge-name"><?php echo sanitize($b['name']); ?></div>
              <div class="badge-desc"><?php echo sanitize($b['description']); ?></div>
              <div class="badge-tier">
                <span class="tier-badge tier-<?php echo $tierClass; ?>"><?php echo $b['tier']; ?></span>
              </div>
            </div>
            <?php if($unlocked): ?>
              <div class="unlocked-badge"><span>✓</span></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
.achievements-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  padding: 30px;
  background: linear-gradient(135deg, rgba(227,59,46,0.15), rgba(255,122,0,0.1));
  border: 1px solid var(--bm-border);
  border-radius: 20px;
}
.achievements-title h2 {
  font-family: 'Oswald', sans-serif;
  font-size: 2rem;
  text-transform: uppercase;
  color: #fff;
  margin-bottom: 5px;
}
.achievements-title p {
  color: var(--bm-muted);
  margin: 0;
}
.achievements-progress {
  text-align: center;
}
.progress-ring-container {
  position: relative;
  width: 120px;
  height: 120px;
}
.progress-ring {
  transform: rotate(-90deg);
}
.progress-ring .progress-bg {
  fill: none;
  stroke: var(--bm-border);
  stroke-width: 10;
}
.progress-ring .progress-fill {
  fill: none;
  stroke: url(#gradient);
  stroke-width: 10;
  stroke-linecap: round;
  stroke: var(--bm-accent);
  transition: stroke-dasharray 0.5s ease;
}
.progress-value {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}
.progress-value .value {
  display: block;
  font-family: 'Oswald', sans-serif;
  font-size: 1.8rem;
  font-weight: 700;
  color: #fff;
}
.progress-value .unit {
  font-size: 0.8rem;
  color: var(--bm-muted);
}
.progress-label {
  margin-top: 10px;
  font-size: 0.85rem;
  color: var(--bm-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.badge-category {
  margin-bottom: 35px;
}
.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--bm-border);
}
.category-header h3 {
  font-family: 'Oswald', sans-serif;
  font-size: 1.3rem;
  text-transform: uppercase;
  color: #fff;
  margin: 0;
}
.category-count {
  background: var(--bm-accent);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}
.badge-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 15px;
}
.badge-card-new {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  background: var(--bm-card);
  border: 1px solid var(--bm-border);
  border-radius: 16px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.badge-card-new::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: var(--bm-border);
}
.badge-card-new.tier-beginner::before { background: #cd7f32; }
.badge-card-new.tier-bronze::before { background: #cd7f32; }
.badge-card-new.tier-intermediate::before { background: #c0c0c0; }
.badge-card-new.tier-silver::before { background: #c0c0c0; }
.badge-card-new.tier-advanced::before { background: #ffd700; }
.badge-card-new.tier-gold::before { background: #ffd700; }
.badge-card-new.tier-elite::before { background: linear-gradient(180deg, #e33b2e, #ff7a00); }
.badge-card-new.tier-special::before { background: linear-gradient(180deg, #9b59b6, #e91e63); }

.badge-card-new.unlocked {
  background: linear-gradient(135deg, rgba(40,167,69,0.1), rgba(40,167,69,0.05));
  border-color: rgba(40,167,69,0.3);
}
.badge-card-new.locked {
  opacity: 0.6;
}
.badge-card-new:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}
.badge-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.05);
  border-radius: 50%;
}
.badge-card-new.locked .badge-icon {
  filter: grayscale(100%);
  opacity: 0.5;
}
.badge-info {
  flex: 1;
}
.badge-name {
  font-family: 'Oswald', sans-serif;
  font-size: 1rem;
  color: #fff;
  margin-bottom: 5px;
}
.badge-desc {
  font-size: 0.85rem;
  color: var(--bm-muted);
  margin-bottom: 8px;
}
.tier-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.tier-badge.tier-beginner, .tier-badge.tier-bronze { background: rgba(205,127,50,0.3); color: #cd7f32; }
.tier-badge.tier-intermediate, .tier-badge.tier-silver { background: rgba(192,192,192,0.3); color: #c0c0c0; }
.tier-badge.tier-advanced, .tier-badge.tier-gold { background: rgba(255,215,0,0.3); color: #ffd700; }
.tier-badge.tier-elite { background: rgba(227,59,46,0.3); color: #e33b2e; }
.tier-badge.tier-special { background: rgba(155,89,182,0.3); color: #9b59b6; }

.unlocked-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 24px;
  height: 24px;
  background: #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 14px;
  font-weight: bold;
}
@media (max-width: 768px) {
  .achievements-header {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  .badge-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
