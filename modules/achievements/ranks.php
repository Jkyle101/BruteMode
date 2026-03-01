<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();
$u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid]));
$p=$u['points'];

// Define all ranks
$ranks = [
  ['name' => 'Recruit', 'min' => 0, 'icon' => '🎖️', 'color' => '#6c757d'],
  ['name' => 'Warrior', 'min' => 100, 'icon' => '⚔️', 'color' => '#28a745'],
  ['name' => 'Gladiator', 'min' => 300, 'icon' => '🗡️', 'color' => '#17a2b8'],
  ['name' => 'Dominator', 'min' => 600, 'icon' => '👑', 'color' => '#9b59b6'],
  ['name' => 'Warlord', 'min' => 1000, 'icon' => '🏰', 'color' => '#e33b2e'],
  ['name' => 'Titan', 'min' => 1500, 'icon' => '⚡', 'color' => '#ff7a00'],
  ['name' => 'Legend', 'min' => 2000, 'icon' => '🔥', 'color' => '#ffd700']
];

// Find current rank
$currentRank = $ranks[0];
$nextRank = null;
for($i = 0; $i < count($ranks); $i++) {
  if($p >= $ranks[$i]['min']) {
    $currentRank = $ranks[$i];
    $nextRank = isset($ranks[$i+1]) ? $ranks[$i+1] : null;
  }
}

// Calculate progress
if($nextRank) {
  $progress = (($p - $currentRank['min']) / ($nextRank['min'] - $currentRank['min'])) * 100;
  $pointsToNext = $nextRank['min'] - $p;
} else {
  $progress = 100;
  $pointsToNext = 0;
}
?>
<div class="container py-4">
  <!-- Rank Header -->
  <div class="rank-header">
    <div class="rank-current">
      <div class="rank-icon" style="background: <?php echo $currentRank['color']; ?>20; border-color: <?php echo $currentRank['color']; ?>;">
        <span style="color: <?php echo $currentRank['color']; ?>;"><?php echo $currentRank['icon']; ?></span>
      </div>
      <div class="rank-info">
        <div class="rank-label">Current Rank</div>
        <div class="rank-name" style="color: <?php echo $currentRank['color']; ?>;"><?php echo $currentRank['name']; ?></div>
        <div class="rank-points"><?php echo (int)$p; ?> Points</div>
      </div>
    </div>
    <?php if($nextRank): ?>
      <div class="rank-next">
        <div class="next-label">Next Rank</div>
        <div class="next-info">
          <span class="next-icon"><?php echo $nextRank['icon']; ?></span>
          <span class="next-name"><?php echo $nextRank['name']; ?></span>
        </div>
        <div class="next-points"><?php echo $pointsToNext; ?> points to go</div>
      </div>
    <?php else: ?>
      <div class="rank-max">
        <div class="max-badge">🏆 MAX RANK</div>
        <div class="max-text">You've reached the highest rank!</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Progress Bar -->
  <div class="rank-progress-section">
    <div class="progress-header">
      <span>Progress to <?php echo $nextRank ? $nextRank['name'] : 'Max'; ?></span>
      <span><?php echo round($progress); ?>%</span>
    </div>
    <div class="rank-progress-bar">
      <div class="rank-progress-fill" style="width: <?php echo min(100, $progress); ?>%; background: linear-gradient(90deg, <?php echo $currentRank['color']; ?>, <?php echo $nextRank ? $nextRank['color'] : $currentRank['color']; ?>);"></div>
    </div>
    <div class="progress-markers">
      <?php foreach($ranks as $rank): ?>
        <div class="marker <?php echo $p >= $rank['min'] ? 'achieved' : ''; ?>" 
             style="left: <?php echo min(100, ($rank['min'] / 2000) * 100); ?>%;">
          <div class="marker-dot"></div>
          <div class="marker-label"><?php echo $rank['name']; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- All Ranks Grid -->
  <div class="ranks-grid">
    <h3 class="ranks-title">All Ranks</h3>
    <div class="rank-cards">
      <?php foreach($ranks as $rank): 
        $isCurrent = $rank['name'] === $currentRank['name'];
        $isAchieved = $p >= $rank['min'];
      ?>
        <div class="rank-card <?php echo $isCurrent ? 'current' : ''; ?> <?php echo $isAchieved ? 'achieved' : 'locked'; ?>" 
             style="--rank-color: <?php echo $rank['color']; ?>;">
          <div class="rank-card-icon"><?php echo $rank['icon']; ?></div>
          <div class="rank-card-name"><?php echo $rank['name']; ?></div>
          <div class="rank-card-points"><?php echo $rank['min']; ?> pts</div>
          <?php if($isCurrent): ?>
            <div class="current-badge">CURRENT</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Points History -->
  <div class="points-info">
    <h3>How to Earn Points</h3>
    <div class="points-list">
      <div class="points-item">
        <span class="points-action">Complete a workout</span>
        <span class="points-value">+10 pts</span>
      </div>
      <div class="points-item">
        <span class="points-action">Maintain 3-day streak</span>
        <span class="points-value">+25 pts</span>
      </div>
      <div class="points-item">
        <span class="points-action">Maintain 7-day streak</span>
        <span class="points-value">+50 pts</span>
      </div>
      <div class="points-item">
        <span class="points-action">Log body measurements</span>
        <span class="points-value">+5 pts</span>
      </div>
      <div class="points-item">
        <span class="points-action">Track habits daily</span>
        <span class="points-value">+3 pts</span>
      </div>
      <div class="points-item">
        <span class="points-action">Set a personal record</span>
        <span class="points-value">+15 pts</span>
      </div>
    </div>
  </div>
</div>

<style>
.rank-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30px;
  background: linear-gradient(135deg, rgba(227,59,46,0.15), rgba(255,122,0,0.1));
  border: 1px solid var(--bm-border);
  border-radius: 20px;
  margin-bottom: 30px;
}
.rank-current {
  display: flex;
  align-items: center;
  gap: 20px;
}
.rank-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 3px solid;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  background: rgba(255,255,255,0.05);
}
.rank-info {
  display: flex;
  flex-direction: column;
}
.rank-label {
  font-size: 0.85rem;
  color: var(--bm-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.rank-name {
  font-family: 'Oswald', sans-serif;
  font-size: 2rem;
  font-weight: 700;
  text-transform: uppercase;
}
.rank-points {
  color: var(--bm-text);
  font-size: 1.1rem;
}
.rank-next, .rank-max {
  text-align: right;
}
.next-label {
  font-size: 0.8rem;
  color: var(--bm-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 5px;
}
.next-info {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: flex-end;
}
.next-icon {
  font-size: 1.5rem;
}
.next-name {
  font-family: 'Oswald', sans-serif;
  font-size: 1.3rem;
  color: #fff;
}
.next-points {
  color: var(--bm-muted);
  font-size: 0.9rem;
  margin-top: 5px;
}
.max-badge {
  background: linear-gradient(135deg, #ffd700, #ff7a00);
  color: #000;
  padding: 8px 20px;
  border-radius: 25px;
  font-weight: 700;
  font-size: 0.9rem;
}
.max-text {
  color: var(--bm-muted);
  margin-top: 5px;
}

.rank-progress-section {
  background: var(--bm-card);
  border: 1px solid var(--bm-border);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 30px;
}
.progress-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  font-weight: 600;
  color: #fff;
}
.rank-progress-bar {
  height: 20px;
  background: var(--bm-border);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 30px;
}
.rank-progress-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.5s ease;
  box-shadow: 0 0 15px currentColor;
}
.progress-markers {
  position: relative;
  height: 40px;
}
.marker {
  position: absolute;
  transform: translateX(-50%);
  text-align: center;
}
.marker-dot {
  width: 12px;
  height: 12px;
  background: var(--bm-border);
  border-radius: 50%;
  margin: 0 auto 5px;
  transition: all 0.3s ease;
}
.marker.achieved .marker-dot {
  background: var(--bm-accent);
  box-shadow: 0 0 10px var(--bm-accent);
}
.marker-label {
  font-size: 0.7rem;
  color: var(--bm-muted);
  white-space: nowrap;
}
.marker.achieved .marker-label {
  color: #fff;
}

.ranks-grid {
  margin-bottom: 30px;
}
.ranks-title {
  font-family: 'Oswald', sans-serif;
  font-size: 1.3rem;
  text-transform: uppercase;
  color: #fff;
  margin-bottom: 20px;
}
.rank-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
}
.rank-card {
  background: var(--bm-card);
  border: 1px solid var(--bm-border);
  border-radius: 16px;
  padding: 20px 15px;
  text-align: center;
  transition: all 0.3s ease;
  position: relative;
}
.rank-card.achieved {
  border-color: var(--rank-color);
  background: rgba(var(--rank-color), 0.1);
}
.rank-card.current {
  border-color: var(--rank-color);
  box-shadow: 0 0 20px rgba(var(--rank-color), 0.3);
  transform: scale(1.05);
}
.rank-card.locked {
  opacity: 0.5;
}
.rank-card:hover {
  transform: translateY(-3px);
}
.rank-card-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}
.rank-card-name {
  font-family: 'Oswald', sans-serif;
  font-size: 1rem;
  color: #fff;
  margin-bottom: 5px;
}
.rank-card-points {
  font-size: 0.8rem;
  color: var(--bm-muted);
}
.current-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--bm-accent);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 10px;
  text-transform: uppercase;
}

.points-info {
  background: var(--bm-card);
  border: 1px solid var(--bm-border);
  border-radius: 16px;
  padding: 25px;
}
.points-info h3 {
  font-family: 'Oswald', sans-serif;
  font-size: 1.3rem;
  text-transform: uppercase;
  color: #fff;
  margin-bottom: 20px;
}
.points-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 12px;
}
.points-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 15px;
  background: rgba(255,255,255,0.03);
  border-radius: 10px;
}
.points-action {
  color: var(--bm-text);
}
.points-value {
  color: var(--bm-accent);
  font-weight: 700;
}

@media (max-width: 768px) {
  .rank-header {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  .rank-current, .rank-next {
    justify-content: center;
  }
  .rank-next, .rank-max {
    text-align: center;
  }
  .next-info {
    justify-content: center;
  }
  .rank-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
