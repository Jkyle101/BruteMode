<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
$uid=auth_user();
$u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid]));
$ww=weekly_workouts($uid);
$goalRaw=strtolower(trim($u['goal']??''));
$weight=(float)($u['weight']??70);
$dates=fetch_all(q("SELECT date FROM workouts WHERE user_id=? AND date>=DATE_SUB(CURDATE(),INTERVAL 35 DAY)","i",[$uid]));
$dow=['Mon'=>0,'Tue'=>0,'Wed'=>0,'Thu'=>0,'Fri'=>0,'Sat'=>0,'Sun'=>0];
foreach($dates as $d){ $dt=DateTime::createFromFormat('Y-m-d',$d['date']); if($dt){ $key=$dt->format('D'); if(isset($dow[$key])) $dow[$key]++; } }
$preferred=array_keys($dow);
usort($preferred,function($a,$b) use ($dow){ return $dow[$b] <=> $dow[$a]; });
$program='Full-Body x2';
$split=[ ['Full Body'], ['Full Body'] ];
if($ww>=3 && $ww<=3){ $program='Full-Body x3'; $split=[ ['Full Body'], ['Full Body'], ['Full Body'] ]; }
elseif($ww==4){ $program='Upper/Lower x2'; $split=[ ['Upper'], ['Lower'], ['Upper'], ['Lower'] ]; }
elseif($ww>=5){ $program='Push/Pull/Legs + Accessories'; $split=[ ['Push'], ['Pull'], ['Legs'], ['Upper Accessories'], ['Conditioning'] ]; }
$dayNames=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$schedule=[];
for($i=0;$i<count($split);$i++){ $day = $preferred[$i % 7]; $schedule[]=['day'=>$day,'type'=>$split[$i][0]]; }
$exLib=[
 'Push'=>['Bench Press','Incline Bench','Overhead Press','Dips'],
 'Pull'=>['Pull-up','Bent Row','Lat Pulldown','Face Pull'],
 'Legs'=>['Squat','Deadlift','Romanian Deadlift','Lunge','Calf Raise'],
 'Upper'=>['Bench Press','Overhead Press','Bent Row','Pull-up','Bicep Curl','Tricep Extension'],
 'Full Body'=>['Squat','Bench Press','Pull-up','Overhead Press','Deadlift','Plank'],
 'Upper Accessories'=>['Dumbbell Press','Lateral Raise','Bicep Curl','Tricep Extension'],
 'Conditioning'=>['Push-up','Plank','Crunch','Lunge']
];
function pickExercises($type,$lib){ $arr=$lib[$type]??$lib['Full Body']; return array_slice($arr,0,4); }
$maintenanceKcal=round($weight*30);
if(strpos($goalRaw,'lose')!==false){ $targetKcal=round($weight*25); }
elseif(strpos($goalRaw,'gain')!==false || strpos($goalRaw,'bulk')!==false){ $targetKcal=round($weight*35); }
else { $targetKcal=$maintenanceKcal; }
$proteinG=round($weight*2.0);
$carbFactor = $ww>=5 ? 4.0 : ($ww>=3 ? 3.0 : 2.0);
$carbsG=round($weight*$carbFactor);
$fatG=round(max(45,$weight*0.8));
$kcalFromMacros = $proteinG*4 + $carbsG*4 + $fatG*9;
$adjustRatio = $kcalFromMacros>0 ? ($targetKcal/$kcalFromMacros) : 1;
$proteinGAdj=round($proteinG*$adjustRatio);
$carbsGAdj=round($carbsG*$adjustRatio);
$fatGAdj=round($fatG*$adjustRatio);
$waterMl=round($weight*35);
$sampleMeals=[
 ['Breakfast','Greek yogurt, oats, berries, almond butter'],
 ['Lunch','Chicken breast, rice, broccoli, olive oil'],
 ['Snack','Protein shake, banana'],
 ['Dinner','Salmon, sweet potato, asparagus'],
 ['Optional','Cottage cheese, nuts']
];
?>
<div class="container py-3">
  <div class="section-banner">
    <div class="section-left">
      <div class="section-icon">🧠</div>
      <div>
        <div class="section-title">Coach Suggestions</div>
        <div class="section-sub">Personalized program and diet based on your schedule</div>
      </div>
    </div>
    <div class="bm-chip"><span>Weekly</span><span><?php echo $ww; ?></span></div>
  </div>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card bg-secondary bm-card">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🏋️</span> Recommended Program</div>
          <div class="display-6 mt-1"><?php echo $program; ?></div>
          <div class="row mt-3">
            <?php foreach($schedule as $s){ $exs=pickExercises($s['type'],$exLib); ?>
              <div class="col-md-6 mb-2">
                <div class="card bg-secondary bm-card">
                  <div class="card-body">
                    <div class="fw-bold"><?php echo $s['day']; ?> • <?php echo $s['type']; ?></div>
                    <ul class="mb-0">
                      <?php foreach($exs as $x){ echo '<li>'.$x.'</li>'; } ?>
                    </ul>
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">📅</span> Your Typical Days</div>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <?php foreach($dayNames as $dn){ $c=$dow[$dn]; $label = $c>0 ? 'Active' : 'Free'; $color = $c>0 ? '#ff7a00' : '#3d434c'; echo '<span class="bm-chip" style="border-color:'.$color.'">'.$dn.' '.$label.'</span>'; } ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card bg-secondary bm-card">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🍽️</span> Diet Suggestions</div>
          <div class="row mt-2">
            <div class="col-md-6">
              <div class="fw-bold">Daily Targets</div>
              <div>Calories: <?php echo $targetKcal; ?> kcal</div>
              <div>Protein: <?php echo $proteinGAdj; ?> g</div>
              <div>Carbs: <?php echo $carbsGAdj; ?> g</div>
              <div>Fats: <?php echo $fatGAdj; ?> g</div>
              <div>Water: <?php echo $waterMl; ?> ml</div>
            </div>
            <div class="col-md-6">
              <div class="fw-bold">Sample Meals</div>
              <ul class="mb-0">
                <?php foreach($sampleMeals as $m){ echo '<li>'.$m[0].': '.$m[1].'</li>'; } ?>
              </ul>
            </div>
          </div>
          <div class="small text-muted mt-2">Adjusted macros based on goal and training frequency</div>
        </div>
      </div>
      <div class="card bg-secondary bm-card mt-3">
        <div class="card-body">
          <div class="fw-bold widget-title"><span class="widget-icon">🔧</span> Tips</div>
          <ul class="mb-0">
            <li>Keep protein high across all meals</li>
            <li>Use heavier carb days on training days</li>
            <li>Track water intake in Habits</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
