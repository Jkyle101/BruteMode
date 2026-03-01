<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();
$qstr=trim($_GET['q']??'');
$mg=trim($_GET['mg']??'');
$sql="SELECT * FROM exercises WHERE (user_id IS NULL OR user_id=?)";
$types="i";
$params=[$uid];
if($qstr){ $sql.=" AND name LIKE ?"; $types.="s"; $params[]="%$qstr%"; }
if($mg){ $sql.=" AND muscle_group=?"; $types.="s"; $params[]=$mg; }
$list=fetch_all(q($sql." ORDER BY is_favorite DESC, name",$types,$params));

$libPath = __DIR__ . '/../../assets/data/exercises_library.json';
$library = [];
if(file_exists($libPath)){
  $json = file_get_contents($libPath);
  $library = json_decode($json, true) ?: [];
}
$libraryFiltered = array_values(array_filter($library,function($e) use ($qstr,$mg){
  $ok = true;
  if($qstr){ $ok = $ok && (stripos($e['name'],$qstr)!==false); }
  if($mg){ $ok = $ok && (strcasecmp($e['muscle_group'],$mg)===0); }
  return $ok;
}));
$muscleData = [
  'Chest' => ['color' => '#e33b2e', 'benefits' => 'Builds upper body strength, improves posture, enhances athletic performance', 'icon' => '💪'],
  'Back' => ['color' => '#ff7a00', 'benefits' => 'Improves posture, strengthens core, reduces back pain', 'icon' => '🔙'],
  'Legs' => ['color' => '#28a745', 'benefits' => 'Increases metabolism, builds overall strength, improves balance', 'icon' => '🦵'],
  'Shoulders' => ['color' => '#17a2b8', 'benefits' => 'Enhances upper body aesthetics, improves shoulder mobility', 'icon' => '🎯'],
  'Arms' => ['color' => '#6f42c1', 'benefits' => 'Builds bicep/tricep strength, improves grip power', 'icon' => '💪'],
  'Core' => ['color' => '#ffc107', 'benefits' => 'Improves stability, strengthens abs, enhances sports performance', 'icon' => '🔥'],
  'Glutes' => ['color' => '#e83e8c', 'benefits' => 'Boosts athletic power, improves hip mobility, shapes physique', 'icon' => '🍑'],
  'Other' => ['color' => '#6c757d', 'benefits' => 'Full body conditioning and fitness improvement', 'icon' => '🏋️']
];
?>
<div class="container py-3">
  <div class="section-banner">
    <div class="section-left">
      <div class="section-icon">📚</div>
      <div>
        <div class="section-title">Exercise Library</div>
        <div class="section-sub">Visual guide with muscle groups, benefits and target areas</div>
      </div>
    </div>
    <div class="bm-chip"><span>Exercises</span><span><?php echo count($list); ?></span></div>
  </div>
  
  <div class="row mt-4">
    <div class="col-md-12">
      <div class="card bg-secondary bm-card">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🌐</span> Global Exercise Library</div>
          <div class="small text-muted">Browse and import exercises into your list</div>
          <div class="row mt-3">
            <?php if(empty($libraryFiltered)){ echo '<div class="text-muted">No matches in the global library.</div>'; } ?>
            <?php foreach($libraryFiltered as $e){ 
              $mData = $muscleData[$e['muscle_group']] ?? $muscleData['Other'];
            ?>
              <div class="col-md-4 mb-3">
                <div class="card bg-secondary bm-card">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-bold"><?php echo sanitize($e['name']); ?></div>
                      <span class="muscle-tag" style="background: <?php echo $mData['color']; ?>"><?php echo sanitize($e['muscle_group']); ?></span>
                    </div>
                    <form method="post" action="/BruteMode/modules/exercises/import_from_library.php">
                      <input type="hidden" name="name" value="<?php echo sanitize($e['name']); ?>">
                      <input type="hidden" name="muscle_group" value="<?php echo sanitize($e['muscle_group']); ?>">
                      <button class="btn btn-primary btn-sm">Add</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="muscle-filter">
    <a href="?q=<?php echo $qstr; ?>" class="muscle-btn <?php echo !$mg ? 'active' : ''; ?>"><span>All</span></a>
    <?php foreach($muscleData as $group => $data): ?>
      <a href="?q=<?php echo $qstr; ?>&mg=<?php echo urlencode($group); ?>" class="muscle-btn <?php echo $mg === $group ? 'active' : ''; ?>" style="--muscle-color: <?php echo $data['color']; ?>">
        <span><?php echo $data['icon']; ?></span><span><?php echo $group; ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="row">
    <div class="col-lg-4 mb-4">
      <div class="body-diagram-card">
        <h5 class="text-center mb-3">Target Areas</h5>
        <div class="body-diagram">
          <svg viewBox="0 0 200 400" class="body-svg">
            <circle cx="100" cy="30" r="20" fill="#3d434c" stroke="#e33b2e" stroke-width="2"/>
            <rect x="92" y="50" width="16" height="15" fill="#3d434c"/>
            <ellipse cx="60" cy="75" rx="25" ry="12" fill="<?php echo ($mg === 'Shoulders' || !$mg) ? '#17a2b8' : '#3d434c'; ?>" class="muscle-area"/>
            <ellipse cx="140" cy="75" rx="25" ry="12" fill="<?php echo ($mg === 'Shoulders' || !$mg) ? '#17a2b8' : '#3d434c'; ?>" class="muscle-area"/>
            <path d="M70 85 Q100 95 130 85 L130 120 Q100 130 70 120 Z" fill="<?php echo ($mg === 'Chest' || !$mg) ? '#e33b2e' : '#3d434c'; ?>" class="muscle-area"/>
            <rect x="30" y="80" width="25" height="60" rx="10" fill="<?php echo ($mg === 'Arms' || !$mg) ? '#6f42c1' : '#3d434c'; ?>" class="muscle-area"/>
            <rect x="145" y="80" width="25" height="60" rx="10" fill="<?php echo ($mg === 'Arms' || !$mg) ? '#6f42c1' : '#3d434c'; ?>" class="muscle-area"/>
            <rect x="75" y="125" width="50" height="70" rx="8" fill="<?php echo ($mg === 'Core' || !$mg) ? '#ffc107' : '#3d434c'; ?>" class="muscle-area"/>
            <ellipse cx="85" cy="200" rx="25" ry="20" fill="<?php echo ($mg === 'Glutes' || !$mg) ? '#e83e8c' : '#3d434c'; ?>" class="muscle-area"/>
            <ellipse cx="115" cy="200" rx="25" ry="20" fill="<?php echo ($mg === 'Glutes' || !$mg) ? '#e83e8c' : '#3d434c'; ?>" class="muscle-area"/>
            <rect x="55" y="220" width="35" height="100" rx="12" fill="<?php echo ($mg === 'Legs' || !$mg) ? '#28a745' : '#3d434c'; ?>" class="muscle-area"/>
            <rect x="110" y="220" width="35" height="100" rx="12" fill="<?php echo ($mg === 'Legs' || !$mg) ? '#28a745' : '#3d434c'; ?>" class="muscle-area"/>
          </svg>
        </div>
        <div class="muscle-legend">
          <?php foreach($muscleData as $group => $data): ?>
            <div class="legend-item <?php echo $mg === $group ? 'active' : ''; ?>">
              <span class="legend-color" style="background: <?php echo $data['color']; ?>"></span>
              <span><?php echo $group; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="search-box mb-4">
        <form class="d-flex" method="get">
          <input class="form-control" name="q" placeholder="Search exercises..." value="<?php echo sanitize($qstr); ?>">
          <input type="hidden" name="mg" value="<?php echo sanitize($mg); ?>">
          <button class="btn btn-primary ms-2">Search</button>
        </form>
      </div>
      
      <div class="exercise-grid">
        <?php if(empty($list)): ?>
          <div class="no-results"><p>No exercises found. Try a different search or add a custom exercise.</p></div>
        <?php else: ?>
          <?php foreach($list as $e): 
            $mData = $muscleData[$e['muscle_group']] ?? $muscleData['Other'];
          ?>
            <div class="exercise-card" style="--accent: <?php echo $mData['color']; ?>">
              <div class="exercise-icon">
                <svg viewBox="0 0 60 60" class="exercise-svg">
                  <circle cx="30" cy="30" r="25" fill="rgba(255,255,255,0.1)" stroke="<?php echo $mData['color']; ?>" stroke-width="2"/>
                  <text x="30" y="38" text-anchor="middle" fill="<?php echo $mData['color']; ?>" font-size="24"><?php echo $mData['icon']; ?></text>
                </svg>
              </div>
              <div class="exercise-info">
                <h5><?php echo sanitize($e['name']); ?></h5>
                <span class="muscle-tag" style="background: <?php echo $mData['color']; ?>"><?php echo sanitize($e['muscle_group']); ?></span>
                <p class="benefits"><?php echo $mData['benefits']; ?></p>
              </div>
              <div class="exercise-actions">
                <form method="post" action="/BruteMode/modules/exercises/edit_exercise.php">
                  <input type="hidden" name="exercise_id" value="<?php echo $e['id']; ?>">
                  <button class="btn btn-sm <?php echo $e['is_favorite']?'btn-warning':'btn-outline-light'; ?>"><?php echo $e['is_favorite']?'★':'☆'; ?></button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-6 mx-auto">
      <div class="add-exercise-card">
        <h5><span>+</span> Add Custom Exercise</h5>
        <form method="post" action="/BruteMode/modules/exercises/add_exercise.php" class="d-flex gap-2">
          <input class="form-control" name="name" placeholder="Exercise name" required>
          <select class="form-select" name="muscle_group">
            <?php foreach(array_keys($muscleData) as $g): ?>
              <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary">Add</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
