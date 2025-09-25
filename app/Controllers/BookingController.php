<?php

class BookingController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new booking
     */
    public function create(Request $request, Response $response)
    {
        try {
            $data = $request->json();
            error_log("Booking create - Received data: " . json_encode($data));

            if (!$data) {
                $response->json([
                    'success' => false,
                    'message' => 'Missing required booking data'
                ], 400);
                return;
            }

            // Get customer ID from session
            $customerId = $this->session->get('user_id');
            if (!$customerId) {
                $response->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
                return;
            }

            // Convert user_id to customer_id
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $response->json([
                    'success' => false,
                    'message' => 'Customer profile not found'
                ], 400);
                return;
            }

            $customerId = $customer['customer_id'];

            // Validate required fields
            $requiredFields = ['cartId', 'orderId', 'renterId', 'items', 'startDate', 'endDate', 'totalAmount', 'advanceAmount'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    $response->json([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ], 400);
                    return;
                }
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update cart with order_id if provided
                if (isset($data['orderId'])) {
                    $stmt = $pdo->prepare("UPDATE carts SET order_id = ? WHERE cart_id = ?");
                    $stmt->execute([$data['orderId'], $data['cartId']]);
                }

                // Get cart dates first
                $stmt = $pdo->prepare("SELECT start_date, end_date FROM carts WHERE cart_id = ?");
                $stmt->execute([$data['cartId']]);
                $cart = $stmt->fetch(PDO::FETCH_ASSOC);

                $startDate = $cart['start_date'] !== '0000-00-00' ? $cart['start_date'] : $data['startDate'];
                $endDate = $cart['end_date'] !== '0000-00-00' ? $cart['end_date'] : $data['endDate'];

                error_log("BookingController - Using dates: startDate={$startDate}, endDate={$endDate}");

                // Create booking record
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (
                        cart_id, customer_id, renter_id, booking_type, 
                        start_date, end_date, total_amount, advance_paid, 
                        status, last_status_updated_by
                    ) VALUES (?, ?, ?, 'Equipment', ?, ?, ?, ?, 'Confirmed', 'Customer')
                ");

                $stmt->execute([
                    $data['cartId'],
                    $customerId,
                    $data['renterId'],
                    $startDate,
                    $endDate,
                    $data['totalAmount'],
                    $data['advanceAmount']
                ]);

                $bookingId = $pdo->lastInsertId();

                // Create booking items
                foreach ($data['items'] as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO bookingitems (
                            booking_id, renter_equipment_id, quantity, price_per_day
                        ) VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $bookingId,
                        $item['renterEquipmentId'],
                        $item['quantity'],
                        $item['pricePerDay']
                    ]);
                }

                // Update cart status to 'Booked'
                $stmt = $pdo->prepare("UPDATE carts SET status = 'Booked' WHERE cart_id = ?");
                $stmt->execute([$data['cartId']]);

                // Create equipment reservations
                foreach ($data['items'] as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO equipment_reservations (
                            renter_equipment_id, customer_id, booking_id, quantity, status, 
                            start_date, end_date, created_at
                        ) VALUES (?, ?, ?, ?, 'Booked', ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $item['renterEquipmentId'],
                        $customerId,
                        $bookingId,
                        $item['quantity'],
                        $startDate,
                        $endDate
                    ]);
                }

                $pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data' => [
                        'bookingId' => $bookingId,
                        'orderId' => $data['orderId']
                    ]
                ]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Booking creation error: " . $e->getMessage());
            error_log("Booking creation stack trace: " . $e->getTraceAsString());
            $response->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment for a booking
     */
    public function confirmPayment(Request $request, Response $response)
    {
        try {
            $data = $request->json();
            error_log("BookingController - confirmPayment received data: " . json_encode($data));

            if (!$data || !isset($data['orderId'])) {
                error_log("BookingController - Missing orderId in data");
                $response->json([
                    'success' => false,
                    'message' => 'Missing order ID'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Find booking by order ID (stored in order_id field of cart)
            $stmt = $pdo->prepare("
                SELECT b.*, c.order_id 
                FROM bookings b 
                JOIN carts c ON b.cart_id = c.cart_id 
                WHERE c.order_id = ? AND b.status = 'Confirmed'
            ");
            $stmt->execute([$data['orderId']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("BookingController - Searching for orderId: " . $data['orderId']);
            error_log("BookingController - Found booking: " . json_encode($booking));

            // If not found, try to find by booking status 'Confirmed' without order_id check
            if (!$booking) {
                error_log("BookingController - No booking found with order_id, trying alternative lookup");
                $stmt = $pdo->prepare("
                    SELECT b.*, c.order_id 
                    FROM bookings b 
                    JOIN carts c ON b.cart_id = c.cart_id 
                    WHERE b.status = 'Confirmed'
                    ORDER BY b.booking_id DESC 
                    LIMIT 1
                ");
                $stmt->execute();
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("BookingController - Alternative booking found: " . json_encode($booking));
            }

            if (!$booking) {
                error_log("BookingController - No booking found for orderId: " . $data['orderId']);
                $response->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
                return;
            }

            // Update booking status to confirmed
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'Confirmed', last_status_updated_by = 'Customer'
                WHERE booking_id = ?
            ");
            $stmt->execute([$booking['booking_id']]);

            // Check if payment record already exists
            $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE booking_id = ?");
            $stmt->execute([$booking['booking_id']]);
            $existingPayment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingPayment) {
                // Create payment record in payments table only if it doesn't exist
                $paymentId = $data['paymentId'] ?? $data['gateway_txn_id'] ?? 'N/A';
                $amount = $booking['advance_paid']; // Use booking amount instead of PayHere amount

                error_log("BookingController - Creating payment record: booking_id={$booking['booking_id']}, paymentId={$paymentId}, amount={$amount}");

                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        booking_id, gateway_txn_id, amount, payment_status, 
                        last_status_updated_by
                    ) VALUES (?, ?, ?, 'Successful', 'Customer')
                ");
                $stmt->execute([
                    $booking['booking_id'],
                    $paymentId,
                    $amount
                ]);
            } else {
                error_log("BookingController - Payment record already exists for booking_id={$booking['booking_id']}");
            }

            error_log("BookingController - Payment confirmation completed");

            // Create notification for successful payment
            $this->createPaymentSuccessNotification($booking['customer_id'], $booking['booking_id'], $booking['advance_paid']);

            $response->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => [
                    'bookingId' => $booking['booking_id'],
                    'status' => 'Confirmed'
                ]
            ]);
            return;
        } catch (\Exception $e) {
            error_log("Payment confirmation error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to confirm payment'
            ], 500);
        }
    }

    /**
     * Cancel payment for a booking
     */
    public function cancelPayment(Request $request, Response $response)
    {
        try {
            $data = $request->json();

            if (!$data || !isset($data['orderId'])) {
                $response->json([
                    'success' => false,
                    'message' => 'Missing order ID'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Find booking by order ID
            $stmt = $pdo->prepare("
                SELECT b.*, c.order_id 
                FROM bookings b 
                JOIN carts c ON b.cart_id = c.cart_id 
                WHERE c.order_id = ? AND b.status = 'Confirmed'
            ");
            $stmt->execute([$data['orderId']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
                return;
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update booking status to cancelled
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'Cancelled', 
                        cancelled_at = NOW(), 
                        cancel_reason = ?,
                        last_status_updated_by = 'Customer'
                    WHERE booking_id = ?
                ");
                $stmt->execute([
                    $data['reason'] ?? 'Payment failed or cancelled',
                    $booking['booking_id']
                ]);

                // Update equipment reservations to cancelled
                $stmt = $pdo->prepare("
                    UPDATE equipment_reservations 
                    SET status = 'Cancelled', updated_at = NOW()
                    WHERE booking_id = ?
                ");
                $stmt->execute([$booking['booking_id']]);

                // Clear cart (set status back to Active and remove items)
                $stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_id = ?");
                $stmt->execute([$booking['cart_id']]);

                $stmt = $pdo->prepare("UPDATE carts SET status = 'Active' WHERE cart_id = ?");
                $stmt->execute([$booking['cart_id']]);

                $pdo->commit();

                $response->json([
                    'success' => true,
                    'message' => 'Payment cancelled successfully'
                ]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Payment cancellation error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to cancel payment'
            ], 500);
        }
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request, Response $response)
    {
        try {
            $orderId = $request->get('orderId');
            if (!$orderId) {
                $response->json([
                    'success' => false,
                    'message' => 'Missing order ID'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("
                SELECT b.status, b.booking_id, b.total_amount, b.advance_paid,
                       p.payment_status, p.transaction_id, p.payment_date
                FROM bookings b 
                JOIN carts c ON b.cart_id = c.cart_id 
                LEFT JOIN payments p ON b.booking_id = p.booking_id
                WHERE c.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $response->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'data' => [
                    'status' => $result['status'],
                    'paymentStatus' => $result['payment_status'],
                    'bookingId' => $result['booking_id'],
                    'amount' => $result['advance_paid']
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Payment status error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to get payment status'
            ], 500);
        }
    }

    /**
     * Handle PayHere webhook notifications
     */
    public function handleWebhook(Request $request, Response $response)
    {
        try {
            $data = $request->json();

            // Log the webhook data for debugging
            error_log("PayHere webhook received: " . json_encode($data));

            if (!$data || !isset($data['order_id'])) {
                $response->json([
                    'success' => false,
                    'message' => 'Invalid webhook data'
                ], 400);
                return;
            }

            $orderId = $data['order_id'];
            $statusCode = $data['status_code'] ?? 0;

            // Status code 2 means payment successful
            if ($statusCode == 2) {
                // Confirm payment
                $confirmData = [
                    'orderId' => $orderId,
                    'paymentId' => $data['payment_id'] ?? '',
                    'amount' => $data['payhere_amount'] ?? 0
                ];

                $this->confirmPayment($request, $response);
            } else {
                // Cancel payment
                $cancelData = [
                    'orderId' => $orderId,
                    'reason' => 'Payment failed via webhook'
                ];

                $this->cancelPayment($request, $response);
            }
        } catch (\Exception $e) {
            error_log("Webhook handling error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to process webhook'
            ], 500);
        }
    }

    /**
     * Show booking details
     */
    public function show(Request $request, Response $response)
    {
        try {
            $bookingId = $request->get('id');

            if (!$bookingId) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking ID is required'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Get booking details with related information including cart dates
            $stmt = $pdo->prepare("
                SELECT b.*, c.order_id, c.start_date, c.end_date, 
                       r.first_name as renter_name, cu.first_name, cu.last_name, cu.email
                FROM bookings b 
                JOIN carts c ON b.cart_id = c.cart_id 
                LEFT JOIN renters r ON b.renter_id = r.renter_id
                LEFT JOIN customers cu ON b.customer_id = cu.customer_id
                WHERE b.booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
                return;
            }

            // Get booking items with equipment details
            $stmt = $pdo->prepare("
                SELECT bi.*, e.name as equipment_name, re.price_per_day, 
                       (SELECT photo_path FROM renter_equipment_photos 
                        WHERE renter_equipment_id = bi.renter_equipment_id 
                        AND display_order = 1 LIMIT 1) as photo_path
                FROM bookingitems bi
                JOIN renter_equipment re ON bi.renter_equipment_id = re.renter_equipment_id
                JOIN equipment e ON re.equipment_id = e.equipment_id
                WHERE bi.booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("BookingController - show booking data: " . json_encode($booking));
            error_log("BookingController - show items data: " . json_encode($items));

            $response->json([
                'success' => true,
                'data' => [
                    'booking' => $booking,
                    'items' => $items
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Booking show error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch booking details'
            ], 500);
        }
    }

    /**
     * Get payment details for a booking
     */
    public function getPaymentDetails(Request $request, Response $response)
    {
        try {
            $bookingId = $request->get('id');

            if (!$bookingId) {
                $response->json([
                    'success' => false,
                    'message' => 'Booking ID is required'
                ], 400);
                return;
            }

            $pdo = Database::getConnection();

            // Get payment details from payments table
            $stmt = $pdo->prepare("
                SELECT * FROM payments 
                WHERE booking_id = ? 
                ORDER BY paid_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$bookingId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $response->json([
                    'success' => false,
                    'message' => 'Payment details not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'data' => [
                    'payment' => $payment
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Get payment details error: " . $e->getMessage());
            $response->json([
                'success' => false,
                'message' => 'Failed to fetch payment details'
            ], 500);
        }
    }

    /**
     * Create notification for successful payment
     */
    private function createPaymentSuccessNotification($customerId, $bookingId, $amount)
    {
        try {
            $pdo = Database::getConnection();

            // Get user_id from customer_id
            $stmt = $pdo->prepare("SELECT user_id FROM customers WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, message, is_read) 
                    VALUES (?, 'PaymentSuccess', ?, 0)
                ");
                $message = "🎉 Payment successful! LKR " . number_format($amount, 2) . " has been received for booking #{$bookingId}. Your equipment rental is now confirmed.";
                $stmt->execute([$customer['user_id'], $message]);
            }
        } catch (\Exception $e) {
            error_log("Failed to create payment success notification: " . $e->getMessage());
        }
    }
}
