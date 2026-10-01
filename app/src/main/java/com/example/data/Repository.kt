package com.example.data

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

data class CartProduct(
    val product: Product,
    val quantity: Int,
    val selectedColor: String,
    val selectedSize: String
) {
    val totalCost: Double
        get() = product.price * quantity
}

class ShoppingRepository(
    private val cartDao: CartDao,
    private val wishlistDao: WishlistDao
) {
    val cartProducts: Flow<List<CartProduct>> = cartDao.getCartItems().map { items ->
        items.mapNotNull { item ->
            val product = ProductCatalog.getProductById(item.productId)
            if (product != null) {
                CartProduct(
                    product = product,
                    quantity = item.quantity,
                    selectedColor = item.selectedColor,
                    selectedSize = item.selectedSize
                )
            } else {
                null
            }
        }
    }

    val wishlistProducts: Flow<List<Product>> = wishlistDao.getWishlistItems().map { items ->
        items.mapNotNull { item ->
            ProductCatalog.getProductById(item.productId)
        }
    }

    suspend fun addToCart(productId: String, quantity: Int, color: String, size: String) {
        cartDao.insertCartItem(CartItem(productId, quantity, color, size))
    }

    suspend fun updateCartQuantity(productId: String, quantity: Int) {
        if (quantity <= 0) {
            cartDao.deleteCartItem(productId)
        } else {
            cartDao.updateQuantity(productId, quantity)
        }
    }

    suspend fun removeFromCart(productId: String) {
        cartDao.deleteCartItem(productId)
    }

    suspend fun clearCart() {
        cartDao.clearCart()
    }

    suspend fun toggleWishlist(productId: String, isCurrentlyWishlisted: Boolean) {
        if (isCurrentlyWishlisted) {
            wishlistDao.deleteWishlist(productId)
        } else {
            wishlistDao.insertWishlist(WishlistItem(productId))
        }
    }
}
