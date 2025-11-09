<?php
$events = Event::all();
?>
<h2>Upcoming events</h2>
<?php if (!$events): ?>
  <p>No events yet.</p>
<?php else: ?>
  <ul>
    <?php foreach($events as $ev): ?>
      <li><strong><?php echo e($ev['title']); ?></strong> — <?php echo e($ev['date']); ?> @ <?php echo e($ev['location']); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>