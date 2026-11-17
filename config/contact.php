<?php
// Número de WhatsApp en formato internacional, solo dígitos.
// El valor de RESCAT_WHATSAPP del servidor tiene prioridad.
// Número predeterminado tomado del número que aparecía en el código compartido.
$whatsappContact = getenv('RESCAT_WHATSAPP') ?: '528136037063';
$emergencyPhone = '8001234575';
