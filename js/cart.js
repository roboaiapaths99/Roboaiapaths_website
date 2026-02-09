// Cart Logic utilizing localStorage

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

function addToCart(id, name, price, image) {
    const cart = getCart();
    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ id, name, price, image, quantity: 1 });
    }

    saveCart(cart);
    alert(`${name} added to cart!`);
    updateCartIcon();
}

function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== id);
    saveCart(cart);
    renderCartPage(); // Re-render if on cart page
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
}

function getCartTotal() {
    const cart = getCart();
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// Update Cart Icon Badge (if exists)
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

    if (!cartContainer) return; // Not on cart page

    const cart = getCart();
    cartContainer.innerHTML = '';

    if (cart.length === 0) {
        cartContainer.innerHTML = '<tr><td colspan="5" class="text-center py-5"><h4>Your cart is empty</h4><a href="kits.html" class="btn btn-primary mt-3">Browse Kits</a></td></tr>';
        if (totalContainer) totalContainer.innerText = '₹ 0';
        return;
    }

    cart.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <img src="${item.image}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 15px;">
                    <span class="fw-bold">${item.name}</span>
                </div>
            </td>
            <td class="align-middle">₹ ${item.price}</td>
            <td class="align-middle">
                <div class="input-group" style="width: 120px;">
                    <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity('${item.id}', -1)">-</button>
                    <input type="text" class="form-control form-control-sm text-center" value="${item.quantity}" readonly>
                    <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity('${item.id}', 1)">+</button>
                </div>
            </td>
            <td class="align-middle">₹ ${item.price * item.quantity}</td>
            <td class="align-middle">
                <button class="btn btn-danger btn-sm rounded-circle" onclick="removeFromCart('${item.id}')"><i class="fas fa-trash"></i></button>
            </td>
        `;
        cartContainer.appendChild(tr);
    });

    if (totalContainer) totalContainer.innerText = '₹ ' + getCartTotal();
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    updateCartIcon();
    renderCartPage();
});
