<?php
/* Shared Razorpay client used by order placement and dashboard payments. */
function rzp_request(string $method, string $path, array $data = []): array {
  if (RAZORPAY_KEY_ID === '' || RAZORPAY_KEY_SECRET === '') {
    return ['ok' => false, 'error' => 'Payment gateway is not configured.'];
  }
  $ch = curl_init('https://api.razorpay.com/v1/' . $path);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 20,
  ];
  if ($method === 'POST') {
    $opts[CURLOPT_POST] = true;
    $opts[CURLOPT_POSTFIELDS] = json_encode($data);
  }
  curl_setopt_array($ch, $opts);
  $res = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($res === false) return ['ok' => false, 'error' => 'Payment gateway unreachable. Try again.'];
  $json = json_decode($res, true);
  if ($code >= 400) return ['ok' => false, 'error' => $json['error']['description'] ?? 'Payment gateway error.'];
  return ['ok' => true, 'data' => $json];
}

function rzp_create_order(array $orderIds, float $total): array {
  $rzp = rzp_request('POST', 'orders', [
    'amount' => (int)round($total * 100),
    'currency' => 'INR',
    'receipt' => 'ms_' . $orderIds[0] . '_' . time(),
    'notes' => ['order_ids' => implode(',', $orderIds)],
  ]);
  if (!$rzp['ok']) return $rzp;
  return [
    'ok' => true,
    'key_id' => RAZORPAY_KEY_ID,
    'razorpay_order_id' => $rzp['data']['id'],
    'amount' => (int)round($total * 100),
    'total' => $total,
  ];
}
