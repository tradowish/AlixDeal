package com.example.ui

import androidx.compose.animation.*
import androidx.compose.animation.core.spring
import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.*
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.drawBehind
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import coil.compose.AsyncImage
import com.example.data.CartProduct
import com.example.data.ChatMessage
import com.example.data.MessageSender
import com.example.data.Product
import com.example.data.ProductCatalog
import com.example.ui.theme.*

// --- MAIN NAV HOST CONTAINER ---
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AlixDealApp(viewModel: ShoppingViewModel) {
    var currentTab by remember { mutableStateOf("Deals") } // Deals, Wishlist, Cart, AI Assistant
    var selectedProduct by remember { mutableStateOf<Product?>(null) }

    val cartItems by viewModel.cartProducts.collectAsState()
    val cartCount = cartItems.sumOf { it.quantity }

    Scaffold(
        bottomBar = {
            NavigationBar(
                modifier = Modifier.windowInsetsPadding(WindowInsets.navigationBars),
                containerColor = MaterialTheme.colorScheme.surface,
                tonalElevation = 8.dp
            ) {
                val tabs = listOf(
                    Triple("Deals", Icons.Default.LocalFireDepartment, "Deals"),
                    Triple("Wishlist", Icons.Default.Favorite, "Wishlist"),
                    Triple("Cart", Icons.Default.ShoppingCart, "Cart"),
                    Triple("AI Assistant", Icons.Default.SmartToy, "AI Assistant")
                )
                tabs.forEach { (tabName, icon, label) ->
                    val isSelected = currentTab == tabName
                    NavigationBarItem(
                        selected = isSelected,
                        onClick = { currentTab = tabName },
                        icon = {
                            BadgedBox(
                                badge = {
                                    if (tabName == "Cart" && cartCount > 0) {
                                        Badge(containerColor = CoralOrange) {
                                            Text(cartCount.toString(), color = Color.White)
                                        }
                                    }
                                }
                            ) {
                                Icon(
                                    imageVector = icon,
                                    contentDescription = label,
                                    modifier = Modifier.size(24.dp)
                                )
                            }
                        },
                        label = { Text(label, fontSize = 11.sp, fontWeight = FontWeight.Medium) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = CoralOrange,
                            selectedTextColor = CoralOrange,
                            indicatorColor = CoralOrange.copy(alpha = 0.15f),
                            unselectedIconColor = LightGray,
                            unselectedTextColor = LightGray
                        ),
                        modifier = Modifier.testTag("nav_tab_${tabName.lowercase().replace(" ", "_")}")
                    )
                }
            }
        },
        contentWindowInsets = WindowInsets.safeDrawing
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            when (currentTab) {
                "Deals" -> DealsScreen(
                    viewModel = viewModel,
                    onProductClick = { selectedProduct = it }
                )
                "Wishlist" -> WishlistScreen(
                    viewModel = viewModel,
                    onProductClick = { selectedProduct = it }
                )
                "Cart" -> CartScreen(viewModel = viewModel)
                "AI Assistant" -> AIAssistantScreen(viewModel = viewModel)
            }

            // Product Details Dialog Overlay
            selectedProduct?.let { product ->
                ProductDetailsDialog(
                    product = product,
                    isWishlisted = viewModel.isWishlisted(product.id),
                    onDismiss = { selectedProduct = null },
                    onAddToCart = { quantity, color, size ->
                        viewModel.addToCart(product, quantity, color, size)
                        selectedProduct = null
                    },
                    onToggleWishlist = {
                        viewModel.toggleWishlist(product)
                    }
                )
            }
        }
    }
}

// --- DEALS EXPLORER SCREEN ---
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DealsScreen(
    viewModel: ShoppingViewModel,
    onProductClick: (Product) -> Unit
) {
    var searchQuery by remember { mutableStateOf("") }
    var isRefreshing by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    val products = viewModel.products.filter {
        it.name.contains(searchQuery, ignoreCase = true) ||
                it.category.contains(searchQuery, ignoreCase = true)
    }

    PullToRefreshBox(
        isRefreshing = isRefreshing,
        onRefresh = {
            scope.launch {
                isRefreshing = true
                delay(900)
                isRefreshing = false
            }
        },
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        Column(
            modifier = Modifier.fillMaxSize()
        ) {
            // App Premium Logo and Search Section
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .drawBehind {
                        val brush = Brush.verticalGradient(
                            colors = listOf(CoralOrange.copy(alpha = 0.12f), Color.Transparent),
                            startY = 0f,
                            endY = size.height
                        )
                        drawRect(brush = brush)
                    }
                    .padding(horizontal = 16.dp, vertical = 12.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                imageVector = Icons.Default.LocalFireDepartment,
                                contentDescription = null,
                                tint = CoralOrange,
                                modifier = Modifier.size(28.dp)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = "AlixDeal",
                                fontSize = 24.sp,
                                fontWeight = FontWeight.Black,
                                color = CoralOrange,
                                letterSpacing = (-0.5).sp
                            )
                        }
                        Text(
                            text = "Curated Daily Discounts • alixdeal.shop",
                            fontSize = 11.sp,
                            color = LightGray,
                            fontWeight = FontWeight.Medium
                        )
                    }

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        // Active Discount Badge in Header
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .background(DiscountGreen.copy(alpha = 0.15f))
                                .border(1.dp, DiscountGreen.copy(alpha = 0.3f), RoundedCornerShape(8.dp))
                                .padding(horizontal = 10.dp, vertical = 6.dp)
                        ) {
                            Text(
                                text = "50% APP OFF",
                                color = DiscountGreen,
                                fontWeight = FontWeight.Bold,
                                fontSize = 10.sp
                            )
                        }

                        // Refresh Button
                        IconButton(
                            onClick = {
                                scope.launch {
                                    isRefreshing = true
                                    delay(900)
                                    isRefreshing = false
                                }
                            },
                            modifier = Modifier.size(36.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.Refresh,
                                contentDescription = "Refresh",
                                tint = CoralOrange
                            )
                        }
                    }
                }

            Spacer(modifier = Modifier.height(16.dp))

            // Search Bar
            OutlinedTextField(
                value = searchQuery,
                onValueChange = { searchQuery = it },
                placeholder = { Text("Search electronic deals, apparel, and more...", fontSize = 13.sp, color = LightGray) },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search icon", tint = CoralOrange) },
                trailingIcon = {
                    if (searchQuery.isNotEmpty()) {
                        IconButton(onClick = { searchQuery = "" }) {
                            Icon(Icons.Default.Clear, contentDescription = "Clear search", tint = LightGray)
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = CoralOrange,
                    unfocusedBorderColor = BorderGray.copy(alpha = 0.4f),
                    focusedContainerColor = MaterialTheme.colorScheme.surface,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surface
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .height(52.dp)
                    .testTag("search_bar")
            )
        }

        // Horizontal Category Tabs
        LazyRow(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 8.dp),
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(ProductCatalog.categories) { category ->
                val isSelected = viewModel.selectedCategory == category
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(100.dp))
                        .background(
                            if (isSelected) CoralOrange else MaterialTheme.colorScheme.surface
                        )
                        .border(
                            width = 1.dp,
                            color = if (isSelected) CoralOrange else BorderGray.copy(alpha = 0.3f),
                            shape = RoundedCornerShape(100.dp)
                        )
                        .clickable { viewModel.setCategory(category) }
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Text(
                        text = category,
                        color = if (isSelected) Color.White else MaterialTheme.colorScheme.onBackground,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }
        }

        // Deal Products Grid
        if (products.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(24.dp),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Icon(
                        imageVector = Icons.Default.SearchOff,
                        contentDescription = "No deals found",
                        tint = LightGray,
                        modifier = Modifier.size(64.dp)
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(
                        text = "No Hot Deals Found",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "We couldn't find matches. Try adjusting your search query.",
                        color = LightGray,
                        textAlign = TextAlign.Center,
                        fontSize = 13.sp
                    )
                }
            }
        } else {
            LazyVerticalGrid(
                columns = GridCells.Fixed(2),
                contentPadding = PaddingValues(16.dp),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                // Flash Banner (Prominent Header)
                item(span = { androidx.compose.foundation.lazy.grid.GridItemSpan(2) }) {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(130.dp),
                        colors = CardDefaults.cardColors(containerColor = SoftCharcoal)
                    ) {
                        Box(modifier = Modifier.fillMaxSize()) {
                            // Artistic Background Gradient lines
                            Box(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .drawBehind {
                                        drawCircle(
                                            color = CoralOrange.copy(alpha = 0.25f),
                                            radius = size.width * 0.4f,
                                            center = Offset(size.width * 0.9f, size.height * 0.2f)
                                        )
                                    }
                            )
                            Row(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .padding(16.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1.3f)) {
                                    Box(
                                        modifier = Modifier
                                            .clip(RoundedCornerShape(4.dp))
                                            .background(CoralOrange)
                                            .padding(horizontal = 6.dp, vertical = 2.dp)
                                    ) {
                                        Text(
                                            "ALIX EXCLUSIVE",
                                            color = Color.White,
                                            fontSize = 9.sp,
                                            fontWeight = FontWeight.Bold
                                        )
                                    }
                                    Spacer(modifier = Modifier.height(6.dp))
                                    Text(
                                        "App Launch Carnival",
                                        color = Color.White,
                                        fontSize = 18.sp,
                                        fontWeight = FontWeight.ExtraBold
                                    )
                                    Spacer(modifier = Modifier.height(2.dp))
                                    Text(
                                        "Use code ALIXDEAL50 for 50% discount on final total checkout!",
                                        color = LightGray,
                                        fontSize = 11.sp,
                                        lineHeight = 14.sp
                                    )
                                }
                                Spacer(modifier = Modifier.width(8.dp))
                                Icon(
                                    imageVector = Icons.Default.ConfirmationNumber,
                                    contentDescription = null,
                                    tint = CoralOrange,
                                    modifier = Modifier
                                        .size(72.dp)
                                        .weight(0.7f)
                                )
                            }
                        }
                    }
                }

                // Grid Items
                items(products, key = { it.id }) { product ->
                    ProductCard(
                        product = product,
                        isWishlisted = viewModel.isWishlisted(product.id),
                        onClick = { onProductClick(product) },
                        onWishlistClick = { viewModel.toggleWishlist(product) }
                    )
                }
            }
        }
    }
}
}

// --- INDIVIDUAL PRODUCT CARD ---
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProductCard(
    product: Product,
    isWishlisted: Boolean,
    onClick: () -> Unit,
    onWishlistClick: () -> Unit
) {
    Card(
        onClick = onClick,
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(1.dp, BorderGray.copy(alpha = 0.2f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        modifier = Modifier
            .fillMaxWidth()
            .testTag("product_card_${product.id}")
    ) {
        Box(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.fillMaxWidth()) {
                // Product Image
                AsyncImage(
                    model = product.imageUrl,
                    contentDescription = product.name,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(130.dp)
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                )

                Column(modifier = Modifier.padding(10.dp)) {
                    // Category & Stars block
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = product.category,
                            fontSize = 10.sp,
                            color = LightGray,
                            fontWeight = FontWeight.SemiBold,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                            modifier = Modifier.weight(1f)
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                imageVector = Icons.Default.Star,
                                contentDescription = null,
                                tint = GoldStar,
                                modifier = Modifier.size(10.dp)
                            )
                            Spacer(modifier = Modifier.width(2.dp))
                            Text(
                                text = product.rating.toString(),
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onBackground
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(4.dp))

                    // Title
                    Text(
                        text = product.name,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        maxLines = 2,
                        minLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        lineHeight = 17.sp,
                        color = MaterialTheme.colorScheme.onBackground
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    // Price Block
                    Row(
                        verticalAlignment = Alignment.Bottom,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = product.displayPrice,
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Black,
                            color = CoralOrange
                        )
                        if (product.originalPrice > product.price) {
                            Text(
                                text = product.displayOriginalPrice,
                                fontSize = 11.sp,
                                color = LightGray,
                                textDecoration = TextDecoration.LineThrough,
                                fontWeight = FontWeight.Normal
                            )
                        }
                    }
                }
            }

            // Top Badges
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(8.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Discount Badge
                if (product.discountPercent > 0) {
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(4.dp))
                            .background(CoralOrange)
                            .padding(horizontal = 6.dp, vertical = 2.dp)
                    ) {
                        Text(
                            "-${product.discountPercent}%",
                            color = Color.White,
                            fontSize = 10.sp,
                            fontWeight = FontWeight.Black
                        )
                    }
                } else {
                    Spacer(modifier = Modifier.width(1.dp))
                }

                // Wishlist Toggle Circle
                Box(
                    modifier = Modifier
                        .size(32.dp)
                        .clip(CircleShape)
                        .background(Color.White.copy(alpha = 0.9f))
                        .clickable { onWishlistClick() }
                        .border(1.dp, BorderGray.copy(alpha = 0.2f), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = if (isWishlisted) Icons.Filled.Favorite else Icons.Outlined.FavoriteBorder,
                        contentDescription = "Toggle Wishlist",
                        tint = if (isWishlisted) CoralOrange else LightGray,
                        modifier = Modifier
                            .size(16.dp)
                            .testTag("wishlist_toggle_${product.id}")
                    )
                }
            }
        }
    }
}

// --- PRODUCT DETAILS SHEET/DIALOG ---
@Composable
fun ProductDetailsDialog(
    product: Product,
    isWishlisted: Boolean,
    onDismiss: () -> Unit,
    onAddToCart: (quantity: Int, color: String, size: String) -> Unit,
    onToggleWishlist: () -> Unit
) {
    var quantity by remember { mutableStateOf(1) }
    var selectedColor by remember { mutableStateOf(product.colors.firstOrNull() ?: "Standard") }
    var selectedSize by remember { mutableStateOf(product.sizes.firstOrNull() ?: "Standard") }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(0.95f)
                .fillMaxHeight(0.90f)
                .clip(RoundedCornerShape(16.dp)),
            color = MaterialTheme.colorScheme.surface,
            tonalElevation = 6.dp
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
            ) {
                // Header image and dismiss actions
                Box(modifier = Modifier.fillMaxWidth()) {
                    AsyncImage(
                        model = product.imageUrl,
                        contentDescription = product.name,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(280.dp)
                            .background(MaterialTheme.colorScheme.surfaceVariant)
                    )

                    // Back & Favorite Circle Buttons on top
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier
                                .size(40.dp)
                                .clip(CircleShape)
                                .background(Color.White.copy(alpha = 0.9f))
                                .clickable { onDismiss() },
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = Icons.AutoMirrored.Default.ArrowBack,
                                contentDescription = "Back",
                                tint = SoftCharcoal,
                                modifier = Modifier.size(20.dp)
                            )
                        }

                        Box(
                            modifier = Modifier
                                .size(40.dp)
                                .clip(CircleShape)
                                .background(Color.White.copy(alpha = 0.9f))
                                .clickable { onToggleWishlist() },
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = if (isWishlisted) Icons.Filled.Favorite else Icons.Outlined.FavoriteBorder,
                                contentDescription = "Toggle Wishlist",
                                tint = if (isWishlisted) CoralOrange else LightGray,
                                modifier = Modifier
                                    .size(20.dp)
                                    .testTag("details_wishlist_toggle")
                            )
                        }
                    }
                }

                Column(modifier = Modifier.padding(18.dp)) {
                    // Category & Hot Deal Tag
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = product.category,
                            color = CoralOrange,
                            fontWeight = FontWeight.Bold,
                            fontSize = 12.sp
                        )
                        if (product.isDeal) {
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(4.dp))
                                    .background(CoralOrange.copy(alpha = 0.15f))
                                    .padding(horizontal = 6.dp, vertical = 2.dp)
                            ) {
                                Text(
                                    "HOT DEAL",
                                    color = CoralOrange,
                                    fontSize = 9.sp,
                                    fontWeight = FontWeight.Black
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    // Title
                    Text(
                        text = product.name,
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Black,
                        color = MaterialTheme.colorScheme.onSurface,
                        lineHeight = 24.sp
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    // Rating Info
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Row {
                            repeat(5) { index ->
                                val active = index < product.rating.toInt()
                                Icon(
                                    imageVector = if (active) Icons.Default.Star else Icons.Default.StarBorder,
                                    contentDescription = null,
                                    tint = if (active) GoldStar else LightGray,
                                    modifier = Modifier.size(16.dp)
                                )
                            }
                        }
                        Text(
                            text = "${product.rating} (${product.reviews} reviews)",
                            fontSize = 12.sp,
                            color = LightGray,
                            fontWeight = FontWeight.Medium
                        )
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    // Price Block
                    Row(
                        verticalAlignment = Alignment.Bottom,
                        horizontalArrangement = Arrangement.spacedBy(10.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(WarmGray.copy(alpha = 0.4f))
                            .padding(12.dp)
                    ) {
                        Column {
                            Text("EXCLUSIVE PRICE", fontSize = 10.sp, color = LightGray, fontWeight = FontWeight.Bold)
                            Row(verticalAlignment = Alignment.Bottom, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                Text(
                                    text = product.displayPrice,
                                    fontSize = 24.sp,
                                    fontWeight = FontWeight.Black,
                                    color = CoralOrange
                                )
                                if (product.originalPrice > product.price) {
                                    Text(
                                        text = product.displayOriginalPrice,
                                        fontSize = 14.sp,
                                        color = LightGray,
                                        textDecoration = TextDecoration.LineThrough,
                                        fontWeight = FontWeight.Medium
                                    )
                                }
                            }
                        }
                        Spacer(modifier = Modifier.weight(1f))
                        if (product.discountPercent > 0) {
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(6.dp))
                                    .background(DiscountGreen)
                                    .padding(horizontal = 8.dp, vertical = 6.dp)
                            ) {
                                Text(
                                    "SAVE ${product.discountPercent}%",
                                    color = Color.White,
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    // Description
                    Text(
                        text = "Product Details",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = product.description,
                        fontSize = 13.sp,
                        lineHeight = 18.sp,
                        color = LightGray
                    )

                    Spacer(modifier = Modifier.height(20.dp))

                    // Color Option Selection
                    if (product.colors.size > 1) {
                        Text(
                            text = "Select Color: $selectedColor",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            product.colors.forEach { color ->
                                val isSelected = selectedColor == color
                                Box(
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(8.dp))
                                        .background(if (isSelected) CoralOrange else WarmGray)
                                        .border(
                                            1.dp,
                                            if (isSelected) CoralOrange else BorderGray,
                                            RoundedCornerShape(8.dp)
                                        )
                                        .clickable { selectedColor = color }
                                        .padding(horizontal = 14.dp, vertical = 8.dp)
                                ) {
                                    Text(
                                        text = color,
                                        color = if (isSelected) Color.White else SoftCharcoal,
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.SemiBold
                                    )
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(20.dp))
                    }

                    // Size Option Selection
                    if (product.sizes.size > 1) {
                        Text(
                            text = "Select Option: $selectedSize",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            product.sizes.forEach { size ->
                                val isSelected = selectedSize == size
                                Box(
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(8.dp))
                                        .background(if (isSelected) CoralOrange else WarmGray)
                                        .border(
                                            1.dp,
                                            if (isSelected) CoralOrange else BorderGray,
                                            RoundedCornerShape(8.dp)
                                        )
                                        .clickable { selectedSize = size }
                                        .padding(horizontal = 14.dp, vertical = 8.dp)
                                ) {
                                    Text(
                                        text = size,
                                        color = if (isSelected) Color.White else SoftCharcoal,
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.SemiBold
                                    )
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(20.dp))
                    }

                    // Quantity Adjuster and Add to Cart Section
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        // - / + Quantity Adjuster
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .clip(RoundedCornerShape(12.dp))
                                .background(WarmGray)
                                .padding(horizontal = 6.dp)
                        ) {
                            IconButton(
                                onClick = { if (quantity > 1) quantity-- },
                                modifier = Modifier.size(36.dp)
                            ) {
                                Icon(Icons.Default.Remove, contentDescription = "Decrease", tint = SoftCharcoal)
                            }
                            Text(
                                text = quantity.toString(),
                                fontSize = 14.sp,
                                fontWeight = FontWeight.Bold,
                                color = SoftCharcoal,
                                modifier = Modifier.padding(horizontal = 10.dp)
                            )
                            IconButton(
                                onClick = { quantity++ },
                                modifier = Modifier.size(36.dp)
                            ) {
                                Icon(Icons.Default.Add, contentDescription = "Increase", tint = SoftCharcoal)
                            }
                        }

                        // Add to Cart Button (Highly descriptive, visual, with testTag)
                        Button(
                            onClick = { onAddToCart(quantity, selectedColor, selectedSize) },
                            colors = ButtonDefaults.buttonColors(containerColor = CoralOrange),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp)
                                .testTag("add_to_cart_button")
                        ) {
                            Icon(Icons.Default.ShoppingCart, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                "Add to Basket",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp
                            )
                        }
                    }
                }
            }
        }
    }
}

// --- LOCAL WISHLIST SCREEN ---
@Composable
fun WishlistScreen(
    viewModel: ShoppingViewModel,
    onProductClick: (Product) -> Unit
) {
    val wishlistItems by viewModel.wishlistProducts.collectAsState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // Wishlist Header
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 12.dp)
        ) {
            Text(
                "My Wishlist",
                fontSize = 24.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                "Your bookmarked daily deals on AlixDeal",
                fontSize = 11.sp,
                color = LightGray
            )
        }

        if (wishlistItems.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(24.dp),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(CoralOrange.copy(alpha = 0.1f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.FavoriteBorder,
                            contentDescription = null,
                            tint = CoralOrange,
                            modifier = Modifier.size(36.dp)
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        "Your Wishlist is Empty",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp,
                        color = MaterialTheme.colorScheme.onBackground
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        "Explore our daily deals and tap the heart icon on any items you want to keep track of here.",
                        color = LightGray,
                        textAlign = TextAlign.Center,
                        fontSize = 13.sp
                    )
                }
            }
        } else {
            LazyColumn(
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                items(wishlistItems, key = { it.id }) { product ->
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { onProductClick(product) },
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        border = BorderStroke(1.dp, BorderGray.copy(alpha = 0.2f))
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(10.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            AsyncImage(
                                model = product.imageUrl,
                                contentDescription = product.name,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier
                                    .size(72.dp)
                                    .clip(RoundedCornerShape(8.dp))
                            )

                            Spacer(modifier = Modifier.width(12.dp))

                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = product.name,
                                    fontSize = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis,
                                    color = MaterialTheme.colorScheme.onBackground
                                )
                                Text(
                                    text = product.category,
                                    fontSize = 10.sp,
                                    color = LightGray
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Row(
                                    verticalAlignment = Alignment.Bottom,
                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                ) {
                                    Text(
                                        text = product.displayPrice,
                                        fontSize = 14.sp,
                                        fontWeight = FontWeight.Black,
                                        color = CoralOrange
                                    )
                                    if (product.originalPrice > product.price) {
                                        Text(
                                            text = product.displayOriginalPrice,
                                            fontSize = 11.sp,
                                            color = LightGray,
                                            textDecoration = TextDecoration.LineThrough
                                        )
                                    }
                                }
                            }

                            // Remove bookmark button
                            IconButton(
                                onClick = { viewModel.toggleWishlist(product) },
                                modifier = Modifier.testTag("wishlist_remove_${product.id}")
                            ) {
                                Icon(
                                    imageVector = Icons.Default.DeleteOutline,
                                    contentDescription = "Remove bookmark",
                                    tint = CoralOrange
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

// --- CART & MULTI-STEP CHECKOUT SCREEN ---
@Composable
fun CartScreen(viewModel: ShoppingViewModel) {
    val cartProducts by viewModel.cartProducts.collectAsState()

    val subtotal = cartProducts.sumOf { it.totalCost }
    val discount = subtotal * viewModel.discountRate
    val shipping = if (subtotal > 0) 4.99 else 0.0
    val total = subtotal - discount + shipping

    AnimatedContent(
        targetState = viewModel.checkoutStep,
        transitionSpec = {
            slideInHorizontally { width -> width } + fadeIn() togetherWith
                    slideOutHorizontally { width -> -width } + fadeOut()
        },
        label = "CheckoutAnimation"
    ) { step ->
        when (step) {
            "CART" -> CartListStep(
                cartProducts = cartProducts,
                subtotal = subtotal,
                discount = discount,
                shipping = shipping,
                total = total,
                appliedPromoCode = viewModel.appliedPromoCode,
                onApplyPromo = { viewModel.applyPromoCode(it) },
                onRemovePromo = { viewModel.removePromoCode() },
                onQuantityChange = { id, qty -> viewModel.updateCartQuantity(id, qty) },
                onRemoveItem = { id -> viewModel.removeFromCart(id) },
                onProceed = { viewModel.navigateToShipping() }
            )
            "SHIPPING" -> ShippingAddressStep(
                onBack = { viewModel.resetCheckout() },
                onSubmit = { name, addr, city, zip ->
                    viewModel.submitShipping(name, addr, city, zip)
                }
            )
            "PAYMENT" -> PaymentCardStep(
                totalCost = total,
                onBack = { viewModel.navigateToShipping() },
                onSubmit = { number, expiry, cvv ->
                    viewModel.submitPayment(number, expiry, cvv)
                }
            )
            "SUCCESS" -> CheckoutSuccessStep(
                orderRef = viewModel.orderReference,
                shippingName = viewModel.shippingName,
                shippingAddress = "${viewModel.shippingAddress}, ${viewModel.shippingCity} ${viewModel.shippingZip}",
                onDismiss = { viewModel.resetCheckout() }
            )
        }
    }
}

// STEP 1: CART LIST & SUMMARY
@Composable
fun CartListStep(
    cartProducts: List<CartProduct>,
    subtotal: Double,
    discount: Double,
    shipping: Double,
    total: Double,
    appliedPromoCode: String,
    onApplyPromo: (String) -> Boolean,
    onRemovePromo: () -> Unit,
    onQuantityChange: (String, Int) -> Unit,
    onRemoveItem: (String) -> Unit,
    onProceed: () -> Unit
) {
    var promoInput by remember { mutableStateOf("") }
    var promoError by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // Header
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 12.dp)
        ) {
            Text(
                "My Shopping Basket",
                fontSize = 24.sp,
                fontWeight = FontWeight.Black,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                "Check items and apply discount codes",
                fontSize = 11.sp,
                color = LightGray
            )
        }

        if (cartProducts.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(24.dp),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(CoralOrange.copy(alpha = 0.1f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.AddShoppingCart,
                            contentDescription = null,
                            tint = CoralOrange,
                            modifier = Modifier.size(36.dp)
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        "Your Basket is Empty",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp,
                        color = MaterialTheme.colorScheme.onBackground
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        "Add some hot daily deals from AlixDeal to check out.",
                        color = LightGray,
                        textAlign = TextAlign.Center,
                        fontSize = 13.sp
                    )
                }
            }
        } else {
            Column(modifier = Modifier.fillMaxSize()) {
                // Cart Items list
                LazyColumn(
                    modifier = Modifier
                        .weight(1f)
                        .padding(horizontal = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(cartProducts, key = { it.product.id }) { cartProduct ->
                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            border = BorderStroke(1.dp, BorderGray.copy(alpha = 0.2f))
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                AsyncImage(
                                    model = cartProduct.product.imageUrl,
                                    contentDescription = cartProduct.product.name,
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier
                                        .size(64.dp)
                                        .clip(RoundedCornerShape(8.dp))
                                )

                                Spacer(modifier = Modifier.width(12.dp))

                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        text = cartProduct.product.name,
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Bold,
                                        maxLines = 1,
                                        overflow = TextOverflow.Ellipsis,
                                        color = MaterialTheme.colorScheme.onBackground
                                    )
                                    Text(
                                        text = "${cartProduct.selectedColor} • ${cartProduct.selectedSize}",
                                        fontSize = 10.sp,
                                        color = LightGray
                                    )
                                    Spacer(modifier = Modifier.height(2.dp))
                                    Text(
                                        text = "${cartProduct.product.displayPrice} each",
                                        fontSize = 11.sp,
                                        color = CoralOrange,
                                        fontWeight = FontWeight.Bold
                                    )
                                }

                                Column(
                                    horizontalAlignment = Alignment.End,
                                    verticalArrangement = Arrangement.Center
                                ) {
                                    // Price and Remove row
                                    IconButton(
                                        onClick = { onRemoveItem(cartProduct.product.id) },
                                        modifier = Modifier
                                            .size(24.dp)
                                            .testTag("cart_remove_${cartProduct.product.id}")
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.Close,
                                            contentDescription = "Remove",
                                            tint = LightGray,
                                            modifier = Modifier.size(16.dp)
                                        )
                                    }

                                    Spacer(modifier = Modifier.height(6.dp))

                                    // - / + Adjuster
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        modifier = Modifier
                                            .clip(RoundedCornerShape(6.dp))
                                            .background(WarmGray)
                                    ) {
                                        IconButton(
                                            onClick = { onQuantityChange(cartProduct.product.id, cartProduct.quantity - 1) },
                                            modifier = Modifier.size(24.dp)
                                        ) {
                                            Icon(Icons.Default.Remove, contentDescription = "Decrease", tint = SoftCharcoal, modifier = Modifier.size(12.dp))
                                        }
                                        Text(
                                            text = cartProduct.quantity.toString(),
                                            fontSize = 12.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = SoftCharcoal,
                                            modifier = Modifier.padding(horizontal = 4.dp)
                                        )
                                        IconButton(
                                            onClick = { onQuantityChange(cartProduct.product.id, cartProduct.quantity + 1) },
                                            modifier = Modifier.size(24.dp)
                                        ) {
                                            Icon(Icons.Default.Add, contentDescription = "Increase", tint = SoftCharcoal, modifier = Modifier.size(12.dp))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Summary and Coupon Section
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
                ) {
                    Column(modifier = Modifier.padding(18.dp)) {
                        // Coupon Code Field
                        if (appliedPromoCode.isEmpty()) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                OutlinedTextField(
                                    value = promoInput,
                                    onValueChange = {
                                        promoInput = it
                                        promoError = false
                                    },
                                    placeholder = { Text("Enter Promo Code (ALIXDEAL50)", fontSize = 11.sp, color = LightGray) },
                                    singleLine = true,
                                    colors = OutlinedTextFieldDefaults.colors(
                                        focusedBorderColor = CoralOrange,
                                        unfocusedBorderColor = BorderGray
                                    ),
                                    isError = promoError,
                                    modifier = Modifier
                                        .weight(1f)
                                        .height(46.dp)
                                        .testTag("promo_code_input")
                                )
                                Button(
                                    onClick = {
                                        val success = onApplyPromo(promoInput)
                                        if (success) {
                                            promoInput = ""
                                        } else {
                                            promoError = true
                                        }
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = SoftCharcoal),
                                    shape = RoundedCornerShape(8.dp),
                                    modifier = Modifier
                                        .height(46.dp)
                                        .testTag("apply_promo_button")
                                ) {
                                    Text("Apply", fontSize = 11.sp)
                                }
                            }
                            if (promoError) {
                                Text(
                                    "Invalid code. Try using 'ALIXDEAL50'",
                                    color = CoralOrange,
                                    fontSize = 10.sp,
                                    modifier = Modifier.padding(top = 2.dp)
                                )
                            }
                        } else {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(DiscountGreen.copy(alpha = 0.1f))
                                    .border(1.dp, DiscountGreen.copy(alpha = 0.2f), RoundedCornerShape(8.dp))
                                    .padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.Celebration, contentDescription = null, tint = DiscountGreen, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text(
                                        "Code '$appliedPromoCode' applied (-50%)",
                                        color = DiscountGreen,
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.Bold
                                    )
                                }
                                Text(
                                    "Remove",
                                    color = CoralOrange,
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier
                                        .clickable { onRemovePromo() }
                                        .testTag("remove_promo_action")
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Receipt Details
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("Basket Subtotal", fontSize = 13.sp, color = LightGray)
                            Text("₹${subtotal.toInt()}", fontSize = 13.sp, color = MaterialTheme.colorScheme.onBackground)
                        }
                        if (discount > 0) {
                            Spacer(modifier = Modifier.height(6.dp))
                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text("App Discount (50%)", fontSize = 13.sp, color = DiscountGreen)
                                Text("-₹${discount.toInt()}", fontSize = 13.sp, color = DiscountGreen, fontWeight = FontWeight.Bold)
                            }
                        }
                        Spacer(modifier = Modifier.height(6.dp))
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("Delivery Charges", fontSize = 13.sp, color = LightGray)
                            Text(if (shipping > 0) "₹${shipping.toInt()}" else "FREE", fontSize = 13.sp, color = if (shipping > 0) MaterialTheme.colorScheme.onBackground else DiscountGreen, fontWeight = FontWeight.Bold)
                        }

                        Divider(modifier = Modifier.padding(vertical = 10.dp), color = BorderGray.copy(alpha = 0.5f))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Total Amount", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
                            Text("₹${total.toInt()}", fontSize = 18.sp, fontWeight = FontWeight.Black, color = CoralOrange)
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Checkout Button
                        Button(
                            onClick = onProceed,
                            colors = ButtonDefaults.buttonColors(containerColor = CoralOrange),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(48.dp)
                                .testTag("checkout_shipping_button")
                        ) {
                            Text("Secure Checkout", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                            Spacer(modifier = Modifier.width(6.dp))
                            Icon(Icons.Default.ArrowForward, contentDescription = null, modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }
        }
    }
}

// STEP 2: SHIPPING ADDRESS
@Composable
fun ShippingAddressStep(
    onBack: () -> Unit,
    onSubmit: (name: String, address: String, city: String, zip: String) -> Unit
) {
    var name by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var city by remember { mutableStateOf("") }
    var zip by remember { mutableStateOf("") }
    var errorVisible by remember { mutableStateOf(false) }

    val focusManager = LocalFocusManager.current

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .verticalScroll(rememberScrollState())
            .padding(18.dp)
    ) {
        // Back Header
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.fillMaxWidth()
        ) {
            IconButton(onClick = onBack) {
                Icon(Icons.AutoMirrored.Default.ArrowBack, contentDescription = "Back", tint = CoralOrange)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Column {
                Text("Shipping Address", fontSize = 20.sp, fontWeight = FontWeight.Black)
                Text("Step 2 of 3 • Where should we deliver?", fontSize = 11.sp, color = LightGray)
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Form Fields
        Text("Recipient Name", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(6.dp))
        OutlinedTextField(
            value = name,
            onValueChange = { name = it },
            placeholder = { Text("John Doe", fontSize = 12.sp) },
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .testTag("shipping_name")
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text("Street Address", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(6.dp))
        OutlinedTextField(
            value = address,
            onValueChange = { address = it },
            placeholder = { Text("123 Shopping Avenue", fontSize = 12.sp) },
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .testTag("shipping_address")
        )

        Spacer(modifier = Modifier.height(16.dp))

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Column(modifier = Modifier.weight(1.2f)) {
                Text("City", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(6.dp))
                OutlinedTextField(
                    value = city,
                    onValueChange = { city = it },
                    placeholder = { Text("San Francisco", fontSize = 12.sp) },
                    singleLine = true,
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("shipping_city")
                )
            }

            Column(modifier = Modifier.weight(0.8f)) {
                Text("PIN Code", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(6.dp))
                OutlinedTextField(
                    value = zip,
                    onValueChange = { zip = it },
                    placeholder = { Text("110001", fontSize = 12.sp) },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("shipping_zip")
                )
            }
        }

        if (errorVisible) {
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                "Please fill in all shipping fields.",
                color = CoralOrange,
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold
            )
        }

        Spacer(modifier = Modifier.height(36.dp))

        Button(
            onClick = {
                focusManager.clearFocus()
                if (name.isBlank() || address.isBlank() || city.isBlank() || zip.isBlank()) {
                    errorVisible = true
                } else {
                    errorVisible = false
                    onSubmit(name, address, city, zip)
                }
            },
            colors = ButtonDefaults.buttonColors(containerColor = CoralOrange),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier
                .fillMaxWidth()
                .height(48.dp)
                .testTag("shipping_submit_button")
        ) {
            Text("Proceed to Secure Payment", fontWeight = FontWeight.Bold, fontSize = 14.sp)
        }
    }
}

// STEP 3: CREDIT CARD SIMULATION
@Composable
fun PaymentCardStep(
    totalCost: Double,
    onBack: () -> Unit,
    onSubmit: (number: String, expiry: String, cvv: String) -> Unit
) {
    var cardNo by remember { mutableStateOf("") }
    var expiry by remember { mutableStateOf("") }
    var cvv by remember { mutableStateOf("") }
    var paymentError by remember { mutableStateOf(false) }

    val focusManager = LocalFocusManager.current

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .verticalScroll(rememberScrollState())
            .padding(18.dp)
    ) {
        // Back Header
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.fillMaxWidth()
        ) {
            IconButton(onClick = onBack) {
                Icon(Icons.AutoMirrored.Default.ArrowBack, contentDescription = "Back", tint = CoralOrange)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Column {
                Text("Secure Payment", fontSize = 20.sp, fontWeight = FontWeight.Black)
                Text("Step 3 of 3 • Mock checkout sandbox", fontSize = 11.sp, color = LightGray)
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Virtual Credit Card Design
        Card(
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = SoftCharcoal),
            modifier = Modifier
                .fillMaxWidth()
                .height(180.dp)
                .padding(vertical = 4.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .drawBehind {
                        drawCircle(
                            color = CoralOrange.copy(alpha = 0.2f),
                            radius = size.width * 0.35f,
                            center = Offset(size.width * 0.15f, size.height * 0.85f)
                        )
                    }
                    .padding(20.dp)
            ) {
                Column(modifier = Modifier.fillMaxSize(), verticalArrangement = Arrangement.SpaceBetween) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.CreditCard,
                            contentDescription = null,
                            tint = CoralOrange,
                            modifier = Modifier.size(28.dp)
                        )
                        Text(
                            "SANDBOX VISA",
                            color = Color.White.copy(alpha = 0.8f),
                            fontWeight = FontWeight.Bold,
                            fontSize = 11.sp
                        )
                    }

                    // Card Number
                    Text(
                        text = if (cardNo.isEmpty()) "•••• •••• •••• ••••" else cardNo.chunked(4).joinToString(" "),
                        color = Color.White,
                        fontSize = 18.sp,
                        fontWeight = FontWeight.SemiBold,
                        letterSpacing = 2.sp
                    )

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Column {
                            Text("CARD HOLDER", fontSize = 8.sp, color = LightGray)
                            Text("ALIXDEAL USER", fontSize = 11.sp, color = Color.White, fontWeight = FontWeight.Bold)
                        }
                        Column(horizontalAlignment = Alignment.End) {
                            Text("EXPIRES", fontSize = 8.sp, color = LightGray)
                            Text(
                                text = if (expiry.isEmpty()) "MM/YY" else "${expiry.take(2)}/${expiry.drop(2)}",
                                fontSize = 11.sp,
                                color = Color.White,
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Card input form
        Text("Credit Card Number (16-digits)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(6.dp))
        OutlinedTextField(
            value = cardNo,
            onValueChange = { if (it.length <= 16 && it.all { c -> c.isDigit() }) cardNo = it },
            placeholder = { Text("4111 2222 3333 4444", fontSize = 12.sp) },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
            modifier = Modifier
                .fillMaxWidth()
                .testTag("payment_card_number")
        )

        Spacer(modifier = Modifier.height(16.dp))

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text("Expiry Date (MMYY)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(6.dp))
                OutlinedTextField(
                    value = expiry,
                    onValueChange = { if (it.length <= 4 && it.all { c -> c.isDigit() }) expiry = it },
                    placeholder = { Text("1227", fontSize = 12.sp) },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("payment_card_expiry")
                )
            }

            Column(modifier = Modifier.weight(1f)) {
                Text("CVV (3-digits)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(6.dp))
                OutlinedTextField(
                    value = cvv,
                    onValueChange = { if (it.length <= 3 && it.all { c -> c.isDigit() }) cvv = it },
                    placeholder = { Text("123", fontSize = 12.sp) },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("payment_card_cvv")
                )
            }
        }

        if (paymentError) {
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                "Invalid card entries. Card Number must be 16-digits, Expiry MMYY (4-digits) and CVV (3-digits).",
                color = CoralOrange,
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                lineHeight = 15.sp
            )
        }

        Spacer(modifier = Modifier.height(28.dp))

        // Total Summary
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(8.dp))
                .background(WarmGray)
                .padding(14.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("Total Amount:", fontSize = 12.sp, color = SoftCharcoal, fontWeight = FontWeight.Medium)
            Text("₹${totalCost.toInt()}", fontSize = 18.sp, color = CoralOrange, fontWeight = FontWeight.Black)
        }

        Spacer(modifier = Modifier.height(20.dp))

        Button(
            onClick = {
                focusManager.clearFocus()
                if (cardNo.length < 16 || expiry.length < 4 || cvv.length < 3) {
                    paymentError = true
                } else {
                    paymentError = false
                    onSubmit(cardNo, expiry, cvv)
                }
            },
            colors = ButtonDefaults.buttonColors(containerColor = CoralOrange),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier
                .fillMaxWidth()
                .height(48.dp)
                .testTag("place_order_button")
        ) {
            Text("Pay Now & Finalize Order", fontWeight = FontWeight.Bold, fontSize = 14.sp)
        }
    }
}

// STEP 4: CELEBRATION ORDER SUCCESS
@Composable
fun CheckoutSuccessStep(
    orderRef: String,
    shippingName: String,
    shippingAddress: String,
    onDismiss: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        // Celebrating Box
        Box(
            modifier = Modifier
                .size(90.dp)
                .clip(CircleShape)
                .background(DiscountGreen.copy(alpha = 0.15f))
                .border(2.dp, DiscountGreen, CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = Icons.Default.CheckCircle,
                contentDescription = null,
                tint = DiscountGreen,
                modifier = Modifier.size(48.dp)
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            text = "Deal Ordered Successfully!",
            fontWeight = FontWeight.Black,
            fontSize = 22.sp,
            color = MaterialTheme.colorScheme.onBackground,
            textAlign = TextAlign.Center
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            text = "Your order has been placed in our system sandbox. Our merchants are preparing your shipment!",
            color = LightGray,
            textAlign = TextAlign.Center,
            fontSize = 12.sp,
            lineHeight = 16.sp
        )

        Spacer(modifier = Modifier.height(28.dp))

        // Receipt Card details
        Card(
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            border = BorderStroke(1.dp, BorderGray.copy(alpha = 0.3f)),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text(
                    text = "RECEIPT SUMMARY",
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold,
                    color = LightGray
                )
                Divider(modifier = Modifier.padding(vertical = 8.dp), color = BorderGray.copy(alpha = 0.3f))

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Order Reference", fontSize = 12.sp, color = LightGray)
                    Text(orderRef, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
                }
                Spacer(modifier = Modifier.height(6.dp))
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Shipment To", fontSize = 12.sp, color = LightGray)
                    Text(shippingName, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onBackground)
                }
                Spacer(modifier = Modifier.height(6.dp))
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Address", fontSize = 12.sp, color = LightGray)
                    Text(
                        shippingAddress,
                        fontSize = 11.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onBackground,
                        modifier = Modifier.widthIn(max = 180.dp)
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(36.dp))

        Button(
            onClick = onDismiss,
            colors = ButtonDefaults.buttonColors(containerColor = SoftCharcoal),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier
                .fillMaxWidth(0.8f)
                .height(48.dp)
                .testTag("success_continue_button")
        ) {
            Text("Back to Deals Explorer", fontWeight = FontWeight.Bold, fontSize = 14.sp)
        }
    }
}

// --- AI SHOPPING ASSISTANT SCREEN ---
@Composable
fun AIAssistantScreen(viewModel: ShoppingViewModel) {
    val messages by viewModel.chatMessages.collectAsState()
    val isAssistantLoading = viewModel.isAssistantLoading
    var userInput by remember { mutableStateOf("") }

    val focusManager = LocalFocusManager.current
    val listState = rememberLazyListState()

    // Auto-scroll chat to latest messages
    LaunchedEffect(messages.size, isAssistantLoading) {
        if (messages.isNotEmpty()) {
            listState.animateScrollToItem(messages.size)
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // Chat Header
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(MaterialTheme.colorScheme.surface)
                .border(width = 0.5.dp, color = BorderGray.copy(alpha = 0.2f))
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(CoralOrange.copy(alpha = 0.15f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.SmartToy,
                        contentDescription = null,
                        tint = CoralOrange,
                        modifier = Modifier.size(22.dp)
                    )
                }
                Spacer(modifier = Modifier.width(10.dp))
                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            "Alix",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Box(
                            modifier = Modifier
                                .size(6.dp)
                                .clip(CircleShape)
                                .background(DiscountGreen)
                        )
                    }
                    Text(
                        "AI Deal Finder • Online",
                        fontSize = 10.sp,
                        color = LightGray,
                        fontWeight = FontWeight.Medium
                    )
                }
            }

            // Reset chat history action
            IconButton(
                onClick = { viewModel.clearChat() },
                modifier = Modifier.testTag("clear_chat_button")
            ) {
                Icon(
                    imageVector = Icons.Default.RotateLeft,
                    contentDescription = "Reset Chat",
                    tint = LightGray,
                    modifier = Modifier.size(20.dp)
                )
            }
        }

        // Quick Suggestion Chips
        LazyRow(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 8.dp),
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            val suggestions = listOf(
                "🎟️ Get active discount code",
                "🎧 What electronics are on sale?",
                "🎒 Recommed standard backpacks",
                "🔥 Show hot deal items"
            )
            items(suggestions) { text ->
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(100.dp))
                        .background(MaterialTheme.colorScheme.surface)
                        .border(
                            width = 1.dp,
                            color = BorderGray.copy(alpha = 0.3f),
                            shape = RoundedCornerShape(100.dp)
                        )
                        .clickable { viewModel.sendChatMessage(text) }
                        .padding(horizontal = 12.dp, vertical = 6.dp)
                ) {
                    Text(
                        text = text,
                        color = MaterialTheme.colorScheme.onBackground,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Medium
                    )
                }
            }
        }

        // Chat Bubble list
        LazyColumn(
            state = listState,
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth()
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(messages) { msg ->
                ChatBubble(message = msg)
            }

            if (isAssistantLoading) {
                item {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp),
                        horizontalArrangement = Arrangement.Start
                    ) {
                        Card(
                            shape = RoundedCornerShape(topStart = 0.dp, topEnd = 16.dp, bottomEnd = 16.dp, bottomStart = 16.dp),
                            colors = CardDefaults.cardColors(containerColor = WarmGray.copy(alpha = 0.5f))
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(4.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    "Alix is searching daily deals...",
                                    fontSize = 11.sp,
                                    color = LightGray,
                                    fontWeight = FontWeight.Medium
                                )
                                CircularProgressIndicator(
                                    modifier = Modifier.size(12.dp),
                                    strokeWidth = 1.5.dp,
                                    color = CoralOrange
                                )
                            }
                        }
                    }
                }
            }
        }

        // Chat Text Input Block
        Card(
            shape = RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedTextField(
                    value = userInput,
                    onValueChange = { userInput = it },
                    placeholder = { Text("Ask Alix about products or coupon codes...", fontSize = 12.sp, color = LightGray) },
                    singleLine = true,
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = CoralOrange,
                        unfocusedBorderColor = BorderGray
                    ),
                    modifier = Modifier
                        .weight(1f)
                        .height(48.dp)
                        .testTag("ai_chat_input")
                )

                IconButton(
                    onClick = {
                        focusManager.clearFocus()
                        if (userInput.isNotBlank()) {
                            viewModel.sendChatMessage(userInput)
                            userInput = ""
                        }
                    },
                    modifier = Modifier
                        .size(46.dp)
                        .clip(CircleShape)
                        .background(CoralOrange)
                        .testTag("ai_send_button")
                ) {
                    Icon(
                        imageVector = Icons.AutoMirrored.Filled.Send,
                        contentDescription = "Send message",
                        tint = Color.White,
                        modifier = Modifier.size(18.dp)
                    )
                }
            }
        }
    }
}

// CHAT BUBBLE VIEW
@Composable
fun ChatBubble(message: ChatMessage) {
    val isUser = message.sender == MessageSender.USER
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = if (isUser) Arrangement.End else Arrangement.Start
    ) {
        if (!isUser) {
            Box(
                modifier = Modifier
                    .size(24.dp)
                    .clip(CircleShape)
                    .background(CoralOrange.copy(alpha = 0.15f))
                    .align(Alignment.Top),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.Default.SmartToy,
                    contentDescription = null,
                    tint = CoralOrange,
                    modifier = Modifier.size(14.dp)
                )
            }
            Spacer(modifier = Modifier.width(6.dp))
        }

        Card(
            shape = if (isUser) {
                RoundedCornerShape(topStart = 16.dp, topEnd = 0.dp, bottomEnd = 16.dp, bottomStart = 16.dp)
            } else {
                RoundedCornerShape(topStart = 0.dp, topEnd = 16.dp, bottomEnd = 16.dp, bottomStart = 16.dp)
            },
            colors = CardDefaults.cardColors(
                containerColor = if (isUser) CoralOrange else WarmGray.copy(alpha = 0.6f)
            ),
            modifier = Modifier.widthIn(max = 280.dp)
        ) {
            Text(
                text = message.text,
                color = if (isUser) Color.White else SoftCharcoal,
                fontSize = 13.sp,
                lineHeight = 18.sp,
                modifier = Modifier.padding(12.dp)
            )
        }
    }
}
