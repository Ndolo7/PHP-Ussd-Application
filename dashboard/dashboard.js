
document.addEventListener('DOMContentLoaded', function() {
    const dispatchForms = document.querySelectorAll('.dispatch-form');
    
    dispatchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent normal form submission
            
            const orderCard = this.closest('.order-card');
            const formData = new FormData(this);
            const button = this.querySelector('button');
            
            // Show loading state
            button.disabled = true;
            button.textContent = 'Processing...';
            orderCard.style.opacity = '0.5';
            
            // Make sure this path matches your file structure
            fetch(this.action, {  // Using the form's action attribute
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Successful dispatch
                    setTimeout(() => {
                        orderCard.remove();
                        
                        // Check if there are any orders left
                        const remainingOrders = document.querySelectorAll('.order-card');
                        if (remainingOrders.length === 0) {
                            const ordersGrid = document.querySelector('.orders-grid');
                            ordersGrid.innerHTML = '<div class="no-orders">No pending orders</div>';
                        }
                    }, 2000);
                } else {
                    throw new Error('Dispatch failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset the card state
                orderCard.style.opacity = '1';
                button.disabled = false;
                button.textContent = 'Mark as Dispatched';
                alert('Failed to dispatch order. Please try again.');
            });
        });
    });
});

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        fetch('get_pending_orders.php')
            .then(response => response.json())
            .then(data => {
                const ordersGrid = document.querySelector('.orders-grid');
                ordersGrid.innerHTML = '';
                
                if (data.orders.length === 0) {
                    ordersGrid.innerHTML = '<div class="no-orders">No pending orders</div>';
                } else {
                    data.orders.forEach(order => {
                        ordersGrid.insertAdjacentHTML('beforeend', createOrderCard(order));
                    });
                    
                    // Re-add event listeners
                    document.querySelectorAll('.dispatch-form').forEach(form => {
                        addDispatchFormListener(form);
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }, 30000); // Refresh every 30 seconds
}

// Method 2: Server-Sent Events (SSE)
function startSSE() {
    const evtSource = new EventSource('sse_handler.php');
    
    evtSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        data.forEach(order => {
            addNewOrder(order);
        });
    };
    
    evtSource.onerror = function(err) {
        console.error("SSE Error:", err);
        evtSource.close();
        // Fall back to auto-refresh method
        startAutoRefresh();
    };
}