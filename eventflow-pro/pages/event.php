<?php
$id = $_GET['id'] ?? null;
$event = $id ? Event::find($id) : null;
if (!$event){
    echo '<p>Select an event from the list.</p>';
    return;
}
?>
<article>
  <h2><?php echo e($event['title']); ?></h2>
  <p><em><?php echo e($event['date']); ?> — <?php echo e($event['location']); ?></em></p>
  <div><?php echo nl2br(e($event['description'] ?? 'No description.')); ?></div>
</article>
