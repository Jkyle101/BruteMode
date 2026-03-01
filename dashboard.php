<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$uid = auth_user();
$u=fetch_one(q("SELECT * FROM users WHERE id=?","i",[$uid]));
$tw=total_workouts($uid);
$ww=weekly_workouts($uid);
$st=current_streak($uid);
$lv=lifetime_volume($uid);
$latestBadge = fetch_one(q("SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.unlocked_at DESC LIMIT 1","i",[$uid]));
?>
<div class="container py-3">
  <div class="bm-hero mb-3 d-flex align-items-center justify-content-between">
    <div>
      <div class="bm-hero-title">Train Hard. Track Smarter.</div>
      <div class="bm-hero-sub">Welcome back, <?php echo sanitize($u['name'] ?: 'Athlete'); ?> • Rank: <?php echo sanitize($u['rank']); ?></div>
    </div>
    <?php
      $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
      $candidates = [
        '/BruteMode/assets/images/logo.png',
        '/BruteMode/assets/images/bmlogo.png',
        '/BruteMode/assets/logo.png'
      ];
      $logoUrl = null;
      foreach($candidates as $c){
        $fs = $doc . $c;
        if(file_exists($fs)){ $logoUrl = $c; break; }
      }
      if($logoUrl){ echo '<img src="'.$logoUrl.'" alt="BruteMode" class="bm-logo">'; }
    ?>
  </div>
  <div class="row">
    <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🏋️</span> Total Workouts</div><div class="display-6"><?php echo $tw; ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🗓️</span> This Week</div><div class="display-6"><?php echo $ww; ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">🔥</span> Streak</div><div class="display-6"><?php echo $st; ?> days</div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary bm-card"><div class="card-body"><div class="fw-bold widget-title"><span class="widget-icon">📈</span> Lifetime Volume</div><div class="display-6"><?php echo number_format($lv,0); ?></div></div></div></div>
  </div>
  <div class="row mt-3">
    <div class="col-md-6"><div class="card bg-secondary bm-card"><div class="card-body">
      <div class="fw-bold">Rank Progress</div>
      <?php $p=$u['points']; $next=100; $label='Warrior'; if($p<100){ $base=0; $next=100; $label='Warrior'; } elseif($p<300){ $base=100; $next=300; $label='Gladiator'; } elseif($p<600){ $base=300; $next=600; $label='Dominator'; } elseif($p<1000){ $base=600; $next=1000; $label='Warlord'; } elseif($p<1500){ $base=1000; $next=1500; $label='Titan'; } else { $base=1500; $next=2000; $label='Legend'; } $pct=min(100, max(0, round((($p-$base)/($next-$base))*100))); ?>
      <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%"><?php echo $pct; ?>%</div></div>
      <div class="small mt-1">Next: <?php echo $label; ?></div>
    </div></div></div>
    <div class="col-md-6"><div class="card bg-secondary bm-card"><div class="card-body">
      <div class="fw-bold">Latest Badge</div>
      <?php if($latestBadge){ echo '<div class="h1">'.$latestBadge['icon'].'</div><div>'.$latestBadge['name'].'</div>'; } else { echo 'None yet'; } ?>
    </div></div></div>
  </div>
  <div class="row mt-3">
    <div class="col-md-12"><div class="card bg-secondary bm-card"><div class="card-body">
      <h6>Weekly Summary</h6>
      <canvas id="weeklyChart" height="120"></canvas>
      <script>
        const wctx = document.getElementById('weeklyChart');
        if(wctx){
          const labels = [<?php for($i=6;$i>=0;$i--){ $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); echo "'$d',"; } ?>];
          const data = [<?php for($i=6;$i>=0;$i--){ $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); $c=fetch_one(q("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND date=?","is",[$uid,$d]))['c']??0; echo $c.','; } ?>];
          new Chart(wctx,{type:'bar',data:{labels,datasets:[{label:'Workouts',data,backgroundColor:'#0d6efd'}]},options:{plugins:{legend:{labels:{color:'#fff'}}},scales:{x:{ticks:{color:'#ccc'}},y:{ticks:{color:'#ccc'},beginAtZero:true,precision:0}}}});
        }
      </script>
    </div></div></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
