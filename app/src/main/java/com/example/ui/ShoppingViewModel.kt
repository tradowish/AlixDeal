package com.example.ui

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.example.data.*
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

class ShoppingViewModel(application: Application) : AndroidViewModel(application) {

    private val database = AppDatabase.getDatabase(application)
    private val repository = ShoppingRepository(database.cartDao(), database.wishlistDao())

    // --- Product Catalog State ---
    var selectedCategory by mutableStateOf("All")
        private set

    val products: List<Product>
        get() = if (selectedCategory == "All") {
            ProductCatalog.products
        } else {
            ProductCatalog.products.filter { it.category == selectedCategory }
        }

    fun setCategory(category: String) {
        selectedCategory = category
    }

    // --- Cart & Wishlist flows from DB ---
    val cartProducts: StateFlow<List<CartProduct>> = repository.cartProducts
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )

    val wishlistProducts: StateFlow<List<Product>> = repository.wishlistProducts
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )

    // --- Cart Actions ---
    fun addToCart(product: Product, quantity: Int, color: String, size: String) {
        viewModelScope.launch {
            repository.addToCart(product.id, quantity, color, size)
        }
    }

    fun updateCartQuantity(productId: String, quantity: Int) {
        viewModelScope.launch {
            repository.updateCartQuantity(productId, quantity)
        }
    }

    fun removeFromCart(productId: String) {
        viewModelScope.launch {
            repository.removeFromCart(productId)
        }
    }

    fun clearCart() {
        viewModelScope.launch {
            repository.clearCart()
        }
    }

    // --- Wishlist Actions ---
    fun toggleWishlist(product: Product) {
        viewModelScope.launch {
            val isCurrentlyWishlisted = wishlistProducts.value.any { it.id == product.id }
            repository.toggleWishlist(product.id, isCurrentlyWishlisted)
        }
    }

    fun isWishlisted(productId: String): Boolean {
        return wishlistProducts.value.any { it.id == productId }
    }

    // --- Checkout & Order States ---
    var checkoutStep by mutableStateOf("CART") // CART, SHIPPING, PAYMENT, SUCCESS
        private set

    var shippingName by mutableStateOf("")
    var shippingAddress by mutableStateOf("")
    var shippingCity by mutableStateOf("")
    var shippingZip by mutableStateOf("")

    var cardNumber by mutableStateOf("")
    var cardExpiry by mutableStateOf("")
    var cardCVV by mutableStateOf("")

    var appliedPromoCode by mutableStateOf("")
        private set

    var discountRate by mutableStateOf(0.0) // 0.0 to 1.0 (e.g. 0.50 is 50% discount)
        private set

    var orderReference by mutableStateOf("")
        private set

    fun applyPromoCode(code: String): Boolean {
        return if (code.trim().uppercase() == "ALIXDEAL50") {
            appliedPromoCode = "ALIXDEAL50"
            discountRate = 0.50
            true
        } else {
            false
        }
    }

    fun removePromoCode() {
        appliedPromoCode = ""
        discountRate = 0.0
    }

    fun navigateToShipping() {
        if (cartProducts.value.isNotEmpty()) {
            checkoutStep = "SHIPPING"
        }
    }

    fun submitShipping(name: String, address: String, city: String, zip: String): Boolean {
        if (name.isBlank() || address.isBlank() || city.isBlank() || zip.isBlank()) {
            return false
        }
        shippingName = name
        shippingAddress = address
        shippingCity = city
        shippingZip = zip
        checkoutStep = "PAYMENT"
        return true
    }

    fun submitPayment(number: String, expiry: String, cvv: String): Boolean {
        if (number.length < 15 || expiry.length < 4 || cvv.length < 3) {
            return false
        }
        cardNumber = number
        cardExpiry = expiry
        cardCVV = cvv
        
        // Finalize order
        orderReference = "AD-${(100000..999999).random()}"
        viewModelScope.launch {
            repository.clearCart()
        }
        checkoutStep = "SUCCESS"
        return true
    }

    fun resetCheckout() {
        checkoutStep = "CART"
        shippingName = ""
        shippingAddress = ""
        shippingCity = ""
        shippingZip = ""
        cardNumber = ""
        cardExpiry = ""
        cardCVV = ""
        appliedPromoCode = ""
        discountRate = 0.0
        orderReference = ""
    }

    // --- AI Assistant Chat State ---
    private val _chatMessages = MutableStateFlow<List<ChatMessage>>(
        listOf(
            ChatMessage(
                sender = MessageSender.ASSISTANT,
                text = "Hello! I am Alix, your personal shopping assistant. 🛍️\n\nLooking for the best price, smart recommendations, or some exclusive discounts? Ask me anything about our daily deals, or type 'discount' to unlock a special coupon!"
            )
        )
    )
    val chatMessages: StateFlow<List<ChatMessage>> = _chatMessages.asStateFlow()

    var isAssistantLoading by mutableStateOf(false)
        private set

    fun sendChatMessage(text: String) {
        if (text.isBlank()) return

        val userMessage = ChatMessage(sender = MessageSender.USER, text = text)
        _chatMessages.update { it + userMessage }

        viewModelScope.launch {
            isAssistantLoading = true
            val history = _chatMessages.value
            val response = GeminiAssistant.getChatResponse(history)
            
            _chatMessages.update { it + ChatMessage(sender = MessageSender.ASSISTANT, text = response) }
            isAssistantLoading = false
        }
    }

    fun clearChat() {
        _chatMessages.value = listOf(
            ChatMessage(
                sender = MessageSender.ASSISTANT,
                text = "Chat history cleared! How can I help you find deals on AlixDeal today?"
            )
        )
    }
}
