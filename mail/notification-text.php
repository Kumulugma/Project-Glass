<?php

/** @var \yii\web\View $this */
/** @var string $subject */
/** @var string $message */
/** @var \app\models\Task $task */

?>
===============================================
📬 <?= strtoupper($subject) ?>

===============================================

<?php if (isset($task) && $task): ?>
ZADANIE: <?= $task->name ?>

<?php if ($task->category): ?>
Kategoria: <?= $task->category ?>

<?php endif; ?>
<?php if ($task->due_date): ?>
Termin: <?= Yii::$app->formatter->asDate($task->due_date) ?>

<?php endif; ?>
<?php if ($task->amount): ?>
Kwota: <?= Yii::$app->formatter->asCurrency($task->amount, $task->currency) ?>

<?php endif; ?>
Status: <?= ucfirst($task->status) ?>


-----------------------------------------------
<?php endif; ?>

<?= $message ?>


-----------------------------------------------
ℹ️ INFORMACJA
-----------------------------------------------
To powiadomienie zostało wygenerowane automatycznie 
przez system GlassSystem.


===============================================
© <?= date('Y') ?> GlassSystem
Wspierane przez K3e.pl (https://k3e.pl)
===============================================