package com.example.data

import androidx.room.Entity
import androidx.room.PrimaryKey

data class Product(
    val id: String,
    val name: String,
    val description: String,
    val price: Double,
    val originalPrice: Double,
    val rating: Float,
    val reviews: Int,
    val category: String,
    val imageUrl: String,
    val isDeal: Boolean = false,
    val isTrending: Boolean = false,
    val colors: List<String> = emptyList(),
    val sizes: List<String> = emptyList()
) {
    val discountPercent: Int
        get() = if (originalPrice > price) {
            (((originalPrice - price) / originalPrice) * 100).toInt()
        } else {
            0
        }

    val displayPrice: String
        get() = "₹${price.toInt()}"

    val displayOriginalPrice: String
        get() = "₹${originalPrice.toInt()}"
}

@Entity(tableName = "cart_items")
data class CartItem(
    @PrimaryKey val productId: String,
    val quantity: Int,
    val selectedColor: String,
    val selectedSize: String
)

@Entity(tableName = "wishlist_items")
data class WishlistItem(
    @PrimaryKey val productId: String
)

object ProductCatalog {
    val categories = listOf("All", "Smart Electronics", "Trending Apparel", "Home Comforts", "Beauty & Tech")

    val products = listOf(
        Product(
            id = "p1",
            name = "Fast 3-in-1 Wireless Charger Stand",
            description = "Fast wireless charging dock for smartphones, smartwatch, and earbuds simultaneously. Compact anti-slip design with over-temperature protection, suitable for bedside and office desk.",
            price = 499.0,
            originalPrice = 1299.0,
            rating = 4.8f,
            reviews = 428,
            category = "Smart Electronics",
            imageUrl = "https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80",
            isDeal = true,
            isTrending = true,
            colors = listOf("Matte Black", "Pure White"),
            sizes = listOf("Standard")
        ),
        Product(
            id = "p2",
            name = "Wireless Bluetooth Earbuds (Deep Bass)",
            description = "True wireless stereo earbuds with crystal-clear high bass and environmental noise cancellation. Offers 40 hours playtime with fast USB-C pocket charging case.",
            price = 699.0,
            originalPrice = 1999.0,
            rating = 4.7f,
            reviews = 812,
            category = "Smart Electronics",
            imageUrl = "https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500&q=80",
            isDeal = true,
            isTrending = true,
            colors = listOf("Pearl White", "Jet Black", "Navy Blue"),
            sizes = listOf("Standard")
        ),
        Product(
            id = "p3",
            name = "Waterproof Laptop College & Office Backpack",
            description = "Heavy-duty waterproof backpack with dedicated 15.6 inch padded laptop compartment, external USB mobile charging port, and anti-theft zipper for safe travel.",
            price = 599.0,
            originalPrice = 1499.0,
            rating = 4.9f,
            reviews = 286,
            category = "Trending Apparel",
            imageUrl = "https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&q=80",
            isDeal = false,
            isTrending = true,
            colors = listOf("Navy Blue", "Charcoal Black", "Military Green"),
            sizes = listOf("Medium (25L)", "Large (35L)")
        ),
        Product(
            id = "p4",
            name = "Ultrasonic Aroma Diffuser & Room Humidifier",
            description = "Aroma essential oil diffuser with cool mist spray and 7-color soothing LED night light. Keeps room air fresh and fragrant for healthy breathing and sound sleep.",
            price = 399.0,
            originalPrice = 899.0,
            rating = 4.6f,
            reviews = 489,
            category = "Home Comforts",
            imageUrl = "https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=500&q=80",
            isDeal = true,
            isTrending = false,
            colors = listOf("Natural Wood", "Dark Walnut"),
            sizes = listOf("300ml", "500ml")
        ),
        Product(
            id = "p5",
            name = "Silicone Sonic Face Cleansing & Massager Brush",
            description = "Waterproof rechargeable facial cleansing brush with gentle high-frequency vibrations. Removes dirt, dead cells, and makeup residue for a natural glowing face.",
            price = 249.0,
            originalPrice = 599.0,
            rating = 4.5f,
            reviews = 315,
            category = "Beauty & Tech",
            imageUrl = "https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=500&q=80",
            isDeal = true,
            isTrending = false,
            colors = listOf("Baby Pink", "Rose Red", "Sky Blue"),
            sizes = listOf("Standard")
        ),
        Product(
            id = "p6",
            name = "Insulated Stainless Steel Coffee & Chai Mug",
            description = "Double-wall vacuum insulated mug with leak-proof flip lid. Keeps hot chai or coffee warm for 6 hours and cold drinks chilled for 12 hours.",
            price = 349.0,
            originalPrice = 799.0,
            rating = 4.7f,
            reviews = 238,
            category = "Home Comforts",
            imageUrl = "https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=500&q=80",
            isDeal = false,
            isTrending = true,
            colors = listOf("Matte Black", "Pearl White", "Steel Grey"),
            sizes = listOf("380ml", "510ml")
        ),
        Product(
            id = "p7",
            name = "Portable USB Rechargeable Mini Juicer Blender",
            description = "Portable 6-blade smoothie and fruit juicer bottle with USB rechargeable battery. Blend fresh fruit shakes, baby food, and protein drinks in seconds on the go.",
            price = 449.0,
            originalPrice = 999.0,
            rating = 4.6f,
            reviews = 562,
            category = "Home Comforts",
            imageUrl = "https://images.unsplash.com/photo-1578643463396-0997cb5328c1?w=500&q=80",
            isDeal = true,
            isTrending = true,
            colors = listOf("Mint Green", "Coral Pink", "Sky Blue"),
            sizes = listOf("400ml")
        ),
        Product(
            id = "p8",
            name = "Bluetooth Calling Smart Watch (Fitness & SpO2)",
            description = "1.85 inch bright HD full touch screen with Bluetooth calling, instant dialer, heart rate, blood oxygen monitor, and 100+ fitness tracking sports modes.",
            price = 1299.0,
            originalPrice = 2999.0,
            rating = 4.8f,
            reviews = 1040,
            category = "Smart Electronics",
            imageUrl = "https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=500&q=80",
            isDeal = true,
            isTrending = true,
            colors = listOf("Space Black", "Midnight Blue", "Rose Gold"),
            sizes = listOf("Free Size (Silicone Strap)")
        ),
        Product(
            id = "p9",
            name = "Pure Cotton Casual Slim Fit Full Sleeve Shirt",
            description = "100% premium breathable cotton casual shirt for men. Soft wash finish with classic spread collar, ideal for office, college, and casual outings.",
            price = 499.0,
            originalPrice = 1199.0,
            rating = 4.7f,
            reviews = 320,
            category = "Trending Apparel",
            imageUrl = "https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=500&q=80",
            isDeal = true,
            isTrending = true,
            colors = listOf("Sky Blue", "Snow White", "Olive Green"),
            sizes = listOf("M (38)", "L (40)", "XL (42)", "XXL (44)")
        ),
        Product(
            id = "p10",
            name = "Smart LED Temperature Display Water Bottle",
            description = "Touch screen LED temperature display vacuum thermos flask. Made with food-grade 304 stainless steel with tea infuser filter.",
            price = 299.0,
            originalPrice = 699.0,
            rating = 4.7f,
            reviews = 415,
            category = "Home Comforts",
            imageUrl = "https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=500&q=80",
            isDeal = true,
            isTrending = false,
            colors = listOf("Matte Black", "Metallic Red", "Royal Blue"),
            sizes = listOf("500ml")
        )
    )

    fun getProductById(id: String): Product? = products.find { it.id == id }
}
