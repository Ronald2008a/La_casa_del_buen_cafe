import React, { useState } from 'react';
import { COFFEE_PRODUCTS, INITIAL_ORDER_DATA } from './data/coffeeData';
import { Product, Category, CustomizationOptions, CartItem, LiveOrder, OrderStage } from './types/coffee';
import { Header } from './components/Header';
import { MenuCard } from './components/MenuCard';
import { CustomizationModal } from './components/CustomizationModal';
import { CartDrawer } from './components/CartDrawer';
import { OrderProgressionView } from './components/OrderProgressionView';
import { OriginLabView } from './components/OriginLabView';
import { MobileSimulatorWrapper } from './components/MobileSimulatorWrapper';
import { Search, Sparkles, Coffee, Heart, ArrowRight, ShieldCheck, Flame, Award } from 'lucide-react';

export default function App() {
  const [activeTab, setActiveTab] = useState<'menu' | 'origins' | 'brew-lab' | 'order-status'>('menu');
  const [selectedCategory, setSelectedCategory] = useState<Category>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedFlavorFilter, setSelectedFlavorFilter] = useState<string | null>(null);

  // Cart State
  const [cartItems, setCartItems] = useState<CartItem[]>([
    INITIAL_ORDER_DATA.items[0] // start with one sample item in bag
  ]);
  const [isCartOpen, setIsCartOpen] = useState(false);

  // Customization Modal State
  const [customizingProduct, setCustomizingProduct] = useState<Product | null>(null);
  const [isCustomizing, setIsCustomizing] = useState(false);

  // Order Fulfillment State
  const [currentOrder, setCurrentOrder] = useState<LiveOrder>(INITIAL_ORDER_DATA);

  // Mobile Hardware Frame Simulation Toggle
  const [isMobileFrame, setIsMobileFrame] = useState(false);

  // Filter products
  const filteredProducts = COFFEE_PRODUCTS.filter((product) => {
    const matchesCategory = selectedCategory === 'all' || product.category === selectedCategory;
    const matchesSearch = 
      product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      product.origin.toLowerCase().includes(searchQuery.toLowerCase()) ||
      product.notes.some(n => n.toLowerCase().includes(searchQuery.toLowerCase()));
    const matchesFlavor = 
      !selectedFlavorFilter || 
      product.notes.some(n => n.toLowerCase().includes(selectedFlavorFilter.toLowerCase()));
    
    return matchesCategory && matchesSearch && matchesFlavor;
  });

  // Open customization modal
  const handleOpenCustomize = (product: Product) => {
    setCustomizingProduct(product);
    setIsCustomizing(true);
  };

  // Quick Add with default options
  const handleQuickAdd = (product: Product) => {
    const defaultOptions: CustomizationOptions = {
      size: '12oz',
      milk: 'Oatly Barista',
      temp: 'Hot (68°C)',
      grind: product.isDrink ? 'Espresso Fine' : 'Whole Bean',
      syrup: 'None',
      shots: 'Double (Standard)',
      notes: ''
    };

    setCartItems(prev => {
      const existing = prev.find(item => item.product.id === product.id && item.customization.syrup === 'None');
      if (existing) {
        return prev.map(item =>
          item.cartItemId === existing.cartItemId
            ? { ...item, quantity: item.quantity + 1, totalPrice: (item.quantity + 1) * item.unitPrice }
            : item
        );
      } else {
        const newItem: CartItem = {
          cartItemId: `item-${Date.now()}-${Math.random()}`,
          product,
          customization: defaultOptions,
          quantity: 1,
          unitPrice: product.price,
          totalPrice: product.price
        };
        return [...prev, newItem];
      }
    });
  };

  // Add customized item to cart
  const handleAddCustomizedToCart = (
    product: Product,
    options: CustomizationOptions,
    quantity: number,
    unitPrice: number
  ) => {
    const newItem: CartItem = {
      cartItemId: `item-${Date.now()}-${Math.random()}`,
      product,
      customization: options,
      quantity,
      unitPrice,
      totalPrice: unitPrice * quantity
    };

    setCartItems(prev => [...prev, newItem]);
    setIsCartOpen(true);
  };

  // Cart quantity handlers
  const handleUpdateCartQuantity = (cartItemId: string, newQty: number) => {
    if (newQty <= 0) {
      handleRemoveCartItem(cartItemId);
      return;
    }
    setCartItems(prev =>
      prev.map(item =>
        item.cartItemId === cartItemId
          ? { ...item, quantity: newQty, totalPrice: newQty * item.unitPrice }
          : item
      )
    );
  };

  const handleRemoveCartItem = (cartItemId: string) => {
    setCartItems(prev => prev.filter(item => item.cartItemId !== cartItemId));
  };

  // Checkout handler
  const handleCheckout = (tipAmount: number, customerName: string) => {
    const subtotal = cartItems.reduce((acc, it) => acc + it.totalPrice, 0);
    const tax = subtotal * 0.0875;
    const total = subtotal + tax + tipAmount;

    const newOrder: LiveOrder = {
      orderId: `#AT-${Math.floor(1000 + Math.random() * 9000)}`,
      customerName,
      pickupCounter: 'COUNTER #B-04',
      lockerPin: `${Math.floor(1000 + Math.random() * 9000)}`,
      placedTime: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      estimatedReadyTime: new Date(Date.now() + 10 * 60000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      stage: 'received',
      baristaName: 'Lucas & Sofia',
      baristaNote: 'Grinding freshly roasted specialty beans & texturing microfoam.',
      items: [...cartItems],
      subtotal,
      tip: tipAmount,
      tax,
      total
    };

    setCurrentOrder(newOrder);
    setCartItems([]);
    setActiveTab('order-status');
  };

  const handleUpdateOrderStage = (newStage: OrderStage) => {
    setCurrentOrder(prev => ({
      ...prev,
      stage: newStage
    }));
  };

  const totalCartCount = cartItems.reduce((acc, it) => acc + it.quantity, 0);

  // Main Layout Render
  const content = (
    <div className="min-h-screen flex flex-col bg-[#FFF8F6] text-[#211A18]">
      
      {/* Top Header */}
      <Header
        activeTab={activeTab}
        onSelectTab={setActiveTab}
        cartCount={totalCartCount}
        onOpenCart={() => setIsCartOpen(true)}
        isMobileFrame={isMobileFrame}
        onToggleMobileFrame={() => setIsMobileFrame(!isMobileFrame)}
        hasActiveOrder={currentOrder.stage !== 'ready'}
      />

      {/* Main Content Router */}
      <main className="flex-1">
        
        {/* TAB 1: MENU & OFFERINGS */}
        {activeTab === 'menu' && (
          <div className="space-y-8 pb-16">
            
            {/* Hero Section */}
            <section className="relative overflow-hidden bg-gradient-to-b from-[#FAEDE8] via-[#FFF8F6] to-[#FFF8F6] border-b border-[#EFE8E1] px-4 py-8 sm:py-14">
              <div className="max-w-6xl mx-auto">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                  
                  {/* Left Column: Headline and Philosophy */}
                  <div className="lg:col-span-7 space-y-4">
                    <div className="inline-flex items-center gap-2 text-xs uppercase tracking-wider font-semibold text-[#94452C] bg-[#FFF8F6] px-3 py-1 rounded-full border border-[#EFE8E1]">
                      <Sparkles className="w-3.5 h-3.5" />
                      <span>Small Batch Third-Wave Roastery</span>
                    </div>

                    <h1 className="font-heading text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-[#211A18] leading-[1.1] text-balance">
                      Sensory warmth meets digital precision.
                    </h1>

                    <p className="text-sm sm:text-base text-[#55433E] leading-relaxed max-w-xl">
                      Roasted in-house weekly on our Giesen cast iron drum. Single-origin microlots, velvety cortados, and laminated sourdough pastries crafted for design-conscious coffee lovers.
                    </p>

                    {/* Quick Highlights strip */}
                    <div className="pt-2 flex flex-wrap items-center gap-4 text-xs font-medium text-[#795746]">
                      <span className="flex items-center gap-1.5">
                        <Award className="w-4 h-4 text-[#94452C]" />
                        <span>SCA 86+ Specialty Grade</span>
                      </span>
                      <span aria-hidden="true" className="text-[#dbc1ba]">·</span>
                      <span className="flex items-center gap-1.5">
                        <Flame className="w-4 h-4 text-[#94452C]" />
                        <span>Direct Farm Trade</span>
                      </span>
                      <span aria-hidden="true" className="text-[#dbc1ba]">·</span>
                      <span className="flex items-center gap-1.5">
                        <ShieldCheck className="w-4 h-4 text-[#4E6E58]" />
                        <span>Contactless Counter Pickup</span>
                      </span>
                    </div>
                  </div>

                  {/* Right Column: Hero Spotlight Feature Card */}
                  <div className="lg:col-span-5">
                    <div 
                      onClick={() => handleOpenCustomize(COFFEE_PRODUCTS[0])}
                      className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-3xl p-5 shadow-warm-ambient hover:shadow-xl transition-all cursor-pointer group"
                    >
                      <div className="relative aspect-[16/10] rounded-2xl overflow-hidden mb-4 bg-[#FAEDE8]">
                        <img
                          src={COFFEE_PRODUCTS[0].image}
                          alt={COFFEE_PRODUCTS[0].name}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div className="absolute top-3 left-3 bg-[#1E1715]/90 backdrop-blur-md text-white text-[11px] font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider">
                          Harvest Feature
                        </div>
                        <div className="absolute bottom-3 right-3 bg-[#FFF8F6]/95 backdrop-blur-md text-[#94452C] font-mono text-xs font-bold px-2.5 py-1 rounded-full">
                          $6.25
                        </div>
                      </div>

                      <div className="space-y-1.5">
                        <span className="text-[11px] font-semibold text-[#88726C] uppercase tracking-wider">
                          Single Origin Pour Over
                        </span>
                        <h3 className="font-heading text-xl font-bold text-[#211A18] group-hover:text-[#94452C] transition-colors">
                          {COFFEE_PRODUCTS[0].name}
                        </h3>
                        <p className="text-xs text-[#795746]">
                          {COFFEE_PRODUCTS[0].notes.join(' · ')}
                        </p>
                        <div className="pt-2 flex items-center justify-between text-xs font-semibold text-[#94452C]">
                          <span>Customize Roast & Cup Size &rarr;</span>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </section>

            {/* Sticky/Smooth Filter Tabs Bar & Search */}
            <div className="max-w-6xl mx-auto px-4 space-y-4">
              
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                {/* Category Pills */}
                <div className="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1">
                  {[
                    { id: 'all', label: 'All Offerings' },
                    { id: 'pourover', label: 'Pour Over Bar' },
                    { id: 'espresso', label: 'Velvet Espresso' },
                    { id: 'cold-nitro', label: 'Cold & Nitro' },
                    { id: 'whole-bean', label: 'Whole Bean Bags' },
                    { id: 'pastries', label: 'Fresh Pastries' }
                  ].map((cat) => (
                    <button
                      key={cat.id}
                      onClick={() => setSelectedCategory(cat.id as Category)}
                      className={`px-4 py-2 text-xs sm:text-sm font-semibold rounded-full whitespace-nowrap transition-all ${
                        selectedCategory === cat.id
                          ? 'bg-[#1E1715] text-white shadow-xs'
                          : 'bg-[#FFFFFF] text-[#55433E] border border-[#EFE8E1] hover:bg-[#FAEDE8]'
                      }`}
                    >
                      {cat.label}
                    </button>
                  ))}
                </div>

                {/* Search Bar matching design guidelines */}
                <div className="relative w-full md:w-64">
                  <Search className="w-4 h-4 text-[#88726C] absolute left-3.5 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search brew, notes, origin..."
                    className="w-full h-10 pl-9 pr-4 rounded-full bg-[#FFFFFF] border border-[#EFE8E1] text-xs text-[#211A18] placeholder-[#8A7B75] focus:outline-none focus:border-[#94452C]"
                  />
                  {searchQuery && (
                    <button
                      onClick={() => setSearchQuery('')}
                      className="text-xs text-[#88726C] hover:text-[#211A18] absolute right-3 top-1/2 -translate-y-1/2"
                    >
                      Clear
                    </button>
                  )}
                </div>

              </div>

              {/* Flavor Profile Filter Row */}
              <div className="flex items-center gap-2 overflow-x-auto scrollbar-none text-xs text-[#55433E]">
                <span className="font-semibold text-[#88726C] shrink-0">Flavors:</span>
                {['Jasmine', 'Bergamot', 'Peach', 'Chocolate', 'Cardamom', 'Hazelnut', 'Honey'].map((flavor) => (
                  <button
                    key={flavor}
                    onClick={() => setSelectedFlavorFilter(selectedFlavorFilter === flavor ? null : flavor)}
                    className={`px-3 py-1 rounded-full border transition-colors shrink-0 ${
                      selectedFlavorFilter === flavor
                        ? 'bg-[#94452C] text-white border-[#94452C]'
                        : 'bg-[#FFF8F6] border-[#EFE8E1] hover:border-[#dbc1ba]'
                    }`}
                  >
                    {flavor}
                  </button>
                ))}
                {selectedFlavorFilter && (
                  <button
                    onClick={() => setSelectedFlavorFilter(null)}
                    className="text-xs text-[#94452C] underline shrink-0 ml-1"
                  >
                    Reset
                  </button>
                )}
              </div>

            </div>

            {/* Product Cards Grid: 4-column fluid mobile / 3-column desktop */}
            <div className="max-w-6xl mx-auto px-4">
              {filteredProducts.length === 0 ? (
                <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-3xl p-12 text-center my-8">
                  <Coffee className="w-10 h-10 text-[#94452C] mx-auto mb-3" />
                  <h3 className="font-heading font-bold text-lg text-[#211A18]">
                    No coffee matching your filter
                  </h3>
                  <p className="text-xs text-[#795746] mt-1 max-w-sm mx-auto">
                    Try clearing your search keyword or selected tasting notes to view the full roastery menu.
                  </p>
                  <button
                    onClick={() => {
                      setSelectedCategory('all');
                      setSearchQuery('');
                      setSelectedFlavorFilter(null);
                    }}
                    className="mt-4 px-4 py-2 rounded-full bg-[#94452C] text-white text-xs font-semibold"
                  >
                    View All Offerings
                  </button>
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {filteredProducts.map((product) => (
                    <MenuCard
                      key={product.id}
                      product={product}
                      onCustomize={handleOpenCustomize}
                      onQuickAdd={handleQuickAdd}
                    />
                  ))}
                </div>
              )}
            </div>

          </div>
        )}

        {/* TAB 2: ORIGINS & SINGLE LOT TERROIRS */}
        {activeTab === 'origins' && (
          <OriginLabView
            onSelectProduct={handleOpenCustomize}
          />
        )}

        {/* TAB 3: BREW RATIO LAB */}
        {activeTab === 'brew-lab' && (
          <OriginLabView
            onSelectProduct={handleOpenCustomize}
          />
        )}

        {/* TAB 4: LIVE ORDER PROGRESSION */}
        {activeTab === 'order-status' && (
          <OrderProgressionView
            order={currentOrder}
            onUpdateStage={handleUpdateOrderStage}
            onNewOrder={() => setActiveTab('menu')}
          />
        )}

      </main>

      {/* Cart Slide-Over Drawer */}
      <CartDrawer
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        items={cartItems}
        onUpdateQuantity={handleUpdateCartQuantity}
        onRemoveItem={handleRemoveCartItem}
        onCheckout={handleCheckout}
      />

      {/* Interactive Customization Bottom Sheet / Modal */}
      <CustomizationModal
        product={customizingProduct}
        isOpen={isCustomizing}
        onClose={() => {
          setIsCustomizing(false);
          setCustomizingProduct(null);
        }}
        onAddToCart={handleAddCustomizedToCart}
      />

      {/* Roastery Footer */}
      <footer className="bg-[#FFFFFF] border-t border-[#EFE8E1] mt-auto">
        <div className="max-w-6xl mx-auto px-4 py-8 sm:py-12">
          <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-6 border-b border-[#EFE8E1]">
            <div>
              <span className="font-heading text-lg font-bold tracking-tight text-[#211A18] block">
                ATELIER ROASTERY & ESPRESSO BAR
              </span>
              <p className="text-xs text-[#795746] mt-1">
                428 Artisan Alley, Roastery District · Open Daily 6:30 AM – 6:00 PM
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-6 text-xs text-[#55433E] font-medium">
              <button onClick={() => setActiveTab('menu')} className="hover:text-[#94452C] transition-colors">
                Menu
              </button>
              <button onClick={() => setActiveTab('origins')} className="hover:text-[#94452C] transition-colors">
                Origin Transparency
              </button>
              <button onClick={() => setActiveTab('brew-lab')} className="hover:text-[#94452C] transition-colors">
                Ratio Guide
              </button>
              <button onClick={() => setActiveTab('order-status')} className="hover:text-[#94452C] transition-colors">
                Live Fulfillment
              </button>
            </div>
          </div>

          <div className="pt-4 flex flex-col sm:flex-row items-center justify-between text-[11px] text-[#88726C] gap-2">
            <span>© 2026 Atelier Specialty Coffee Roasters. Crafted with unbleached ceramic warmth & digital precision.</span>
            <div className="flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-[#4E6E58]" />
              <span className="font-medium text-[#4E6E58]">Roastery Bar Active & Pouring</span>
            </div>
          </div>
        </div>
      </footer>

    </div>
  );

  return (
    <MobileSimulatorWrapper
      isMobileMode={isMobileFrame}
      onCloseMobileMode={() => setIsMobileFrame(false)}
    >
      {content}
    </MobileSimulatorWrapper>
  );
}
