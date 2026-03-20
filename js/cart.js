// Cart Logic utilizing localStorage with OTP Integration

const CART_KEY = 'robo_aia_cart';

// Initialize cart if not exists
function getCart() {
    const cart = localStorage.getItem(CART_KEY);
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartIcon();
}

function isLoggedIn() {
    return localStorage.getItem('user_logged_in') === 'true';
}

function getUserMobile() {
    return localStorage.getItem('user_mobile');
}

// Intercept Add to Cart
let pendingCartAction = null;

function addToCart(id, name, price, image) {
    if (!isLoggedIn()) {
        pendingCartAction = { action: 'add', id, name, price, image };
        showOtpModal();
        return;
    }

    executeAddToCart(id, name, price, image);
}

function executeAddToCart(id, name, price, image) {
    const cart = getCart();
    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ id, name, price: Number(price), image, quantity: 1 });
    }

    saveCart(cart);
    showToast(`"${name}" added to cart!`, 'success', { text: 'View Cart ➔', url: 'cart.html' });
    updateCartIcon();
}

function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== id);
    saveCart(cart);
    renderCartPage();
}

function updateQuantity(id, change) {
    const cart = getCart();
    const item = cart.find(item => item.id === id);

    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
            return;
        }
    }
    saveCart(cart);
    renderCartPage();
}

function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateCartIcon();
    renderCartPage();
}

function getCartSubtotal() {
    const cart = getCart();
    return cart.reduce((total, item) => {
        const price = Number(item.price) || 0;
        const qty = Number(item.quantity) || 0;
        return total + (price * qty);
    }, 0);
}

function getCartGST(subtotal) {
    return Math.round(subtotal * 0.18);
}

function formatCurrency(amount) {
    return '₹ ' + Number(amount).toLocaleString('en-IN');
}

// Update Cart Icon Badge 
function updateCartIcon() {
    const cart = getCart();
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.innerText = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Render Cart Page
function renderCartPage() {
    const cartContainer = document.getElementById('cart-items');
    const totalContainer = document.getElementById('cart-total');

    if (!cartContainer) return;

    const cart = getCart();
    cartContainer.innerHTML = '';

    if (cart.length === 0) {
        cartContainer.innerHTML = '<tr><td colspan="5" class="text-center py-5"><h4>Your cart is empty</h4><a href="kits.html" class="btn btn-primary mt-3">Browse Kits</a></td></tr>';
        if (totalContainer) totalContainer.innerText = '₹ 0';
        const gstContainer = document.getElementById('cart-gst');
        const finalContainer = document.getElementById('cart-total-final');
        if (gstContainer) gstContainer.innerText = '₹ 0';
        if (finalContainer) finalContainer.innerText = '₹ 0';
        return;
    }

    cart.forEach(item => {
        const tr = document.createElement('tr');
        const itemLineTotal = Number(item.price) * Number(item.quantity);
        tr.innerHTML = `
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <img src="${item.image}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px; margin-right: 15px; border: 1px solid #e2e8f0;">
                    <span class="fw-bold fs-6">${item.name}</span>
                </div>
            </td>
            <td class="align-middle fw-semibold">${formatCurrency(item.price)}</td>
            <td class="align-middle">
                <div class="d-flex align-items-center bg-light rounded-pill p-1" style="width: fit-content; border: 1px solid #e2e8f0;">
                    <button class="btn-qty" onclick="updateQuantity('${item.id}', -1)"><i class="fas fa-minus small"></i></button>
                    <input type="text" class="item-qty-input" value="${item.quantity}" readonly>
                    <button class="btn-qty" onclick="updateQuantity('${item.id}', 1)"><i class="fas fa-plus small"></i></button>
                </div>
            </td>
            <td class="align-middle fw-bold text-primary">${formatCurrency(itemLineTotal)}</td>
            <td class="align-middle text-end">
                <button class="btn btn-link text-danger p-0 me-3" onclick="removeFromCart('${item.id}')"><i class="fas fa-trash-alt"></i></button>
            </td>
        `;
        cartContainer.appendChild(tr);
    });

    const subtotal = getCartSubtotal();
    const gst = getCartGST(subtotal);
    const finalTotal = subtotal + gst;

    if (totalContainer) totalContainer.innerText = formatCurrency(subtotal);

    const gstContainer = document.getElementById('cart-gst');
    const finalContainer = document.getElementById('cart-total-final');

    if (gstContainer) gstContainer.innerText = formatCurrency(gst);
    if (finalContainer) finalContainer.innerText = formatCurrency(finalTotal);
}

// --- OTP Logic ---
function injectOtpModal() {
    if (document.getElementById('otpModal')) return;

    const html = `
    <div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
          <div class="modal-body">
            <h4 class="fw-bold mb-4">Login to Continue</h4>
            <div id="otpStep1">
                <p class="text-muted small">Enter your 10-digit mobile number to login/register securely.</p>
                <input type="tel" id="otpMobileInput" class="form-control mb-3 text-center fs-5" placeholder="Mobile Number" maxlength="10">
                <button class="btn btn-primary w-100 py-2 rounded-pill fw-bold" onclick="requestOtp()" id="btnSendOtp">Send OTP</button>
            </div>
            <div id="otpStep2" style="display:none;">
                <p class="text-muted small">Enter the 4-digit OTP sent to <span id="displayMobile" class="fw-bold"></span></p>
                <input type="text" id="otpVerifyInput" class="form-control mb-3 text-center fs-4 letter-spacing-1" placeholder="• • • •" maxlength="4">
                <button class="btn btn-success w-100 py-2 rounded-pill fw-bold" onclick="verifyOtp()" id="btnVerifyOtp">Verify & Login</button>
                <button class="btn btn-link text-muted mt-2 small" onclick="changeNumber()">Change Number</button>
            </div>
            <button class="btn btn-light mt-3 py-1 btn-sm rounded-pill" data-bs-dismiss="modal" onclick="pendingCartAction=null">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}



let otpModalInstance = null;

function showOtpModal() {
    if (!otpModalInstance) {
        otpModalInstance = new bootstrap.Modal(document.getElementById('otpModal'), {});
    }
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    document.getElementById('otpMobileInput').value = '';
    otpModalInstance.show();
}

async function requestOtp() {
    const mobile = document.getElementById('otpMobileInput').value.trim();
    if (!/^[0-9]{10}$/.test(mobile)) {
        showToast('Please enter a valid 10-digit mobile number', 'error');
        return;
    }

    const btn = document.getElementById('btnSendOtp');
    btn.disabled = true;
    btn.innerText = 'Sending...';

    try {
        const res = await fetch('api/send_otp.php', {
            method: 'POST',
            body: JSON.stringify({ mobile }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('otpStep1').style.display = 'none';
            document.getElementById('otpStep2').style.display = 'block';
            document.getElementById('displayMobile').innerText = mobile;
            showToast('OTP sent securely!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Network error, please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Send OTP';
    }
}

async function verifyOtp() {
    const mobile = document.getElementById('otpMobileInput').value.trim();
    const otp = document.getElementById('otpVerifyInput').value.trim();
    if (otp.length !== 4) {
        showToast('Please enter the 4-digit OTP', 'error');
        return;
    }

    const btn = document.getElementById('btnVerifyOtp');
    btn.disabled = true;
    btn.innerText = 'Verifying...';

    try {
        const res = await fetch('api/verify_otp.php', {
            method: 'POST',
            body: JSON.stringify({ mobile, otp }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.status === 'success') {
            localStorage.setItem('user_logged_in', 'true');
            localStorage.setItem('user_mobile', data.mobile);
            otpModalInstance.hide();
            showToast('Logged in successfully', 'success');

            // Execute pending action
            if (pendingCartAction && pendingCartAction.action === 'add') {
                executeAddToCart(pendingCartAction.id, pendingCartAction.name, pendingCartAction.price, pendingCartAction.image);
                pendingCartAction = null;
            } else if (pendingCartAction && pendingCartAction.action === 'checkout') {
                window.location.href = 'checkout.html';
            } else if (pendingCartAction && pendingCartAction.action === 'view_cart') {
                window.location.href = 'cart.html';
            } else {
                window.location.reload(); // Fallback to refresh state
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Network error, please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Verify & Login';
    }
}

function changeNumber() {
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    document.getElementById('otpVerifyInput').value = '';
}

function requireLoginForCheckout(event) {
    if (!isLoggedIn()) {
        event.preventDefault();
        pendingCartAction = { action: 'checkout' };
        showOtpModal();
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    // If user is on cart page but not logged in, kick them out
    if (window.location.pathname.endsWith('cart.html') && !isLoggedIn()) {
        window.location.href = 'kits.html';
        return;
    }

    injectOtpModal();
    updateCartIcon();
    renderCartPage();

    // Globally intercept any links to the cart page
    const cartNavLinks = document.querySelectorAll('a[href="cart.html"]');
    cartNavLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            if (!isLoggedIn()) {
                e.preventDefault();
                pendingCartAction = { action: 'view_cart' };
                showOtpModal();
            }
        });
    });
});
