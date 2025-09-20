<?php

/**
 * Temporary Admin Controller
 * For development purposes only - to verify users manually
 * This should be removed when the actual admin dashboard is ready
 */

class TempAdminController extends Controller
{
    private CustomerRepository $customerRepository;

    public function __construct()
    {
        $this->customerRepository = new CustomerRepository();
    }

    /**
     * Verify a customer's documents
     * POST /api/temp-admin/verify-customer
     */
    public function verifyCustomer(Request $request, Response $response): void
    {
        try {
            // Get request data
            $data = $request->getFormData();
            $userId = $data['user_id'] ?? null;

            if (!$userId) {
                $response->setStatusCode(400);
                $response->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ]);
                return;
            }

            // Update verification status
            $success = $this->customerRepository->updateVerificationStatus($userId, 'Yes');

            if ($success) {
                $response->json([
                    'success' => true,
                    'message' => 'Customer verified successfully',
                    'data' => [
                        'user_id' => $userId,
                        'verification_status' => 'Yes'
                    ]
                ]);
            } else {
                $response->setStatusCode(500);
                $response->json([
                    'success' => false,
                    'message' => 'Failed to verify customer'
                ]);
            }
        } catch (Exception $e) {
            $this->log("Temp admin verify customer error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to verify customer');
        }
    }

    /**
     * Get all customers with pending verification
     * GET /api/temp-admin/pending-verifications
     */
    public function getPendingVerifications(Request $request, Response $response): void
    {
        try {
            $customers = $this->customerRepository->getCustomersByVerificationStatus('Pending');

            $response->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (Exception $e) {
            $this->log("Temp admin get pending verifications error: " . $e->getMessage(), 'ERROR');
            $response->serverError('Failed to get pending verifications');
        }
    }
}
