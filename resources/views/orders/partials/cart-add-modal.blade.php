@once
    @push('scripts')
        <script>
            (() => {
                const cartList = document.querySelector('[data-cart-items]');
                const cartTotal = document.getElementById('cart-grand-total');
                const cartBadges = document.querySelectorAll('[data-cart-count-badge]');
                const cartLinks = document.querySelectorAll('[data-cart-count-link]');
                const csrfToken = document.querySelector('input[name="_token"]')?.value;
                const loginUrl = @json(route('login'));
                const currency = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                });

                const updateCartBadges = (cartCount) => {
                    cartBadges.forEach((badge) => {
                        badge.textContent = cartCount;
                        badge.hidden = false;
                    });
                    cartLinks.forEach((link) => {
                        link.setAttribute('aria-label', `Keranjang, ${cartCount} produk`);
                    });
                };

                const updateCartView = (result) => {
                    updateCartBadges(result.cart_count);
                    if (!cartList) return;

                    for (const item of result.items) {
                        let row = cartList.querySelector(`[data-cart-item="${item.id}"]`);
                        if (!row) {
                            row = document.createElement('article');
                            row.dataset.cartItem = item.id;
                            row.className = 'cart-item';

                            const details = document.createElement('div');
                            const name = document.createElement('h2');
                            name.className = 'font-extrabold text-[#2C1810]';
                            name.dataset.cartItemName = '';
                            const price = document.createElement('p');
                            price.className = 'text-sm text-slate-500';
                            price.dataset.cartItemPrice = '';
                            details.className = 'cart-item-details';
                            details.append(name, price);

                            const controls = document.createElement('div');
                            controls.className = 'cart-item-controls';
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = cartList.dataset.updateUrlTemplate.replace('__MENU_ITEM_ID__', item.id);
                            form.className = 'cart-quantity-form';
                            const token = document.createElement('input');
                            token.type = 'hidden';
                            token.name = '_token';
                            token.value = csrfToken || '';
                            const method = document.createElement('input');
                            method.type = 'hidden';
                            method.name = '_method';
                            method.value = 'PATCH';
                            const quantity = document.createElement('input');
                            quantity.type = 'number';
                            quantity.name = 'quantity';
                            quantity.min = '0';
                            quantity.max = '99';
                            quantity.setAttribute('aria-label', 'Jumlah');
                            quantity.className = 'cart-quantity-input';
                            const update = document.createElement('button');
                            update.className = 'cart-update-button';
                            update.textContent = 'Perbarui';
                            form.append(token, method, quantity, update);

                            const subtotal = document.createElement('strong');
                            subtotal.dataset.cartItemSubtotal = '';
                            subtotal.className = 'cart-item-subtotal';
                            controls.append(form, subtotal);
                            row.append(details, controls);
                            cartList.append(row);
                        }

                        row.querySelector('[data-cart-item-name]').textContent = item.name;
                        row.querySelector('[data-cart-item-price]').textContent = currency.format(item.unit_price);
                        row.querySelector('input[name="quantity"]').value = item.quantity;
                        row.querySelector('[data-cart-item-subtotal]').textContent = currency.format(item.line_total);
                        document.querySelector(`[data-additional-item="${item.id}"]`)?.remove();
                    }

                    cartTotal.textContent = currency.format(result.total);
                };

                const showFeedback = (text, {error = false, loginRequired = false, duration = 3500} = {}) => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: text,
                            type: error ? 'error' : 'success',
                            duration,
                            action: loginRequired ? {label: 'Masuk', url: loginUrl} : null,
                        },
                    }));
                };

                document.addEventListener('submit', async (event) => {
                    const form = event.target.closest('[data-cart-add-form]');
                    if (!form) return;

                    event.preventDefault();
                    if (form.dataset.customerAuthenticated !== 'true') {
                        showFeedback(
                            'Masuk dulu untuk menambahkan produk ke keranjang.',
                            {error: true, loginRequired: true, duration: 6000},
                        );
                        return;
                    }

                    const button = form.querySelector('button[type="submit"]');
                    if (button?.disabled) return;
                    if (button) button.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            showFeedback(
                                response.status === 401
                                    ? 'Sesi customer berakhir. Silakan masuk kembali untuk melanjutkan belanja.'
                                    : (result.message || 'Produk tidak dapat ditambahkan. Silakan coba lagi.'),
                                {error: true, loginRequired: response.status === 401, duration: 5000},
                            );
                            return;
                        }

                        updateCartView(result);
                        const productName = result.items.find((item) => item.id === result.added_item_id)?.name ?? 'Produk';
                        showFeedback(`${productName} ditambahkan ke keranjang.`);
                    } catch {
                        showFeedback('Terjadi kendala saat memperbarui keranjang. Periksa koneksi lalu coba lagi.', {error: true, duration: 5000});
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
            })();
        </script>
    @endpush
@endonce
