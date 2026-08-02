/**
 * M-Pesa Payment Handler
 * Manages payment flows for bookings and checkout
 */

class MpesaPaymentHandler {
    constructor() {
        this.isProcessing = false;
        this.checkInterval = null;
        this.maxCheckAttempts = 60; // Check for 5 minutes (60 * 5 seconds)
        this.currentCheckAttempts = 0;
    }

    /**
     * Initiate payment for booking
     *
     * @param {number} bookingId - Booking ID
     * @param {number} amount - Amount in KES
     * @param {string} phoneNumber - Customer phone number
     * @param {function} onSuccess - Callback on success
     * @param {function} onError - Callback on error
     */
    async initiateBookingPayment(bookingId, amount, phoneNumber, onSuccess, onError) {
        if (this.isProcessing) {
            if (onError) onError('Payment is already in progress');
            return;
        }

        this.isProcessing = true;
        this.currentCheckAttempts = 0;

        try {
            // Show loading state
            this.showLoadingState('Processing your payment...');

            // Validate inputs
            if (!bookingId || !amount || !phoneNumber) {
                throw new Error('Missing required payment information');
            }

            if (amount < 1 || amount > 999999) {
                throw new Error('Invalid amount. Must be between 1 and 999999');
            }

            // Make payment request
            const response = await fetch('/public/ajax/mpesa/stk-push.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    amount: amount,
                    phone_number: phoneNumber
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Payment initiation failed');
            }

            // Show success message
            this.showSuccessMessage(
                'Check your phone!',
                'Please enter your M-Pesa PIN to complete the payment'
            );

            // Start polling for payment status
            this.pollPaymentStatus(
                result.data.transaction_id,
                onSuccess,
                onError
            );

        } catch (error) {
            this.isProcessing = false;
            this.hideLoadingState();

            const errorMessage = error.message || 'An error occurred while initiating payment';
            this.showErrorMessage('Payment Failed', errorMessage);

            if (onError) onError(errorMessage);
        }
    }

    /**
     * Poll for payment status
     *
     * @param {number} transactionId - Transaction ID to check
     * @param {function} onSuccess - Callback on success
     * @param {function} onError - Callback on error
     */
    pollPaymentStatus(transactionId, onSuccess, onError) {
        this.checkInterval = setInterval(async () => {
            this.currentCheckAttempts++;

            try {
                const response = await fetch(
                    `/public/ajax/mpesa/transaction-query.php?transaction_id=${transactionId}`,
                    {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }
                );

                const result = await response.json();

                if (!result.success) {
                    throw new Error('Failed to query payment status');
                }

                const transaction = result.transaction;

                // Check status
                if (transaction.status === 'completed') {
                    clearInterval(this.checkInterval);
                    this.isProcessing = false;
                    this.hideLoadingState();

                    this.showSuccessMessage(
                        'Payment Successful!',
                        `Receipt: ${transaction.mpesa_receipt_number}`
                    );

                    if (onSuccess) {
                        onSuccess({
                            transaction_id: transaction.id,
                            mpesa_receipt: transaction.mpesa_receipt_number,
                            amount: transaction.amount,
                            status: transaction.status
                        });
                    }

                } else if (transaction.status === 'failed') {
                    clearInterval(this.checkInterval);
                    this.isProcessing = false;
                    this.hideLoadingState();

                    const errorMsg = transaction.result_desc || 'Payment was declined';
                    this.showErrorMessage('Payment Failed', errorMsg);

                    if (onError) onError(errorMsg);

                } else if (this.currentCheckAttempts >= this.maxCheckAttempts) {
                    clearInterval(this.checkInterval);
                    this.isProcessing = false;
                    this.hideLoadingState();

                    this.showErrorMessage(
                        'Payment Timeout',
                        'The payment is still pending. Please try again or contact support.'
                    );

                    if (onError) onError('Payment timeout');
                }

            } catch (error) {
                // Continue polling even on error
                console.error('Status check error:', error);
            }
        }, 5000); // Check every 5 seconds
    }

    /**
     * Show loading state
     */
    showLoadingState(message = 'Processing...') {
        // Create or update loading modal
        let modal = document.getElementById('mpesa-loading-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'mpesa-loading-modal';
            document.body.appendChild(modal);
        }

        modal.innerHTML = `
            <div class="mpesa-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            ">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                    max-width: 400px;
                ">
                    <div class="mpesa-spinner" style="
                        width: 50px;
                        height: 50px;
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #B76E79;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 1.5rem;
                    "></div>
                    <p style="color: #333; font-size: 1rem; margin: 0;">${message}</p>
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        const modal = document.getElementById('mpesa-loading-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Show success message
     */
    showSuccessMessage(title, message) {
        this.showAlert(title, message, 'success', '#28a745');
    }

    /**
     * Show error message
     */
    showErrorMessage(title, message) {
        this.showAlert(title, message, 'error', '#dc3545');
    }

    /**
     * Show alert modal
     */
    showAlert(title, message, type, color) {
        let modal = document.getElementById('mpesa-alert-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'mpesa-alert-modal';
            document.body.appendChild(modal);
        }

        const icon = type === 'success' ? '✓' : '⚠';

        modal.innerHTML = `
            <div class="mpesa-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            ">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                    max-width: 400px;
                ">
                    <div style="
                        width: 60px;
                        height: 60px;
                        border-radius: 50%;
                        background: ${color}20;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 1.5rem;
                        font-size: 2rem;
                        color: ${color};
                    ">${icon}</div>
                    <h3 style="color: #333; margin: 0 0 0.5rem; font-size: 1.3rem;">${title}</h3>
                    <p style="color: #666; margin: 0 0 1.5rem; line-height: 1.6;">${message}</p>
                    <button onclick="this.closest('[id=mpesa-alert-modal]').style.display='none'" style="
                        background: ${color};
                        color: white;
                        border: none;
                        padding: 0.75rem 1.5rem;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 0.95rem;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Close
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Cancel ongoing payment
     */
    cancelPayment() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        this.isProcessing = false;
        this.hideLoadingState();
    }
}

// Initialize globally
const mpesaPaymentHandler = new MpesaPaymentHandler();

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MpesaPaymentHandler;
}
