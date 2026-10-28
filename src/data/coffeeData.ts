import { Product } from '../types/coffee';

export const COFFEE_PRODUCTS: Product[] = [
  {
    id: 'ethiopia-yirgacheffe',
    name: 'Ethiopia Yirgacheffe Natural',
    category: 'pourover',
    origin: 'Gedeo Zone, Yirgacheffe, Ethiopia',
    farm: 'Konga Cooperative micro-producers',
    elevation: '2,150 MASL',
    process: 'Natural Sun-Dried on Raised African Beds',
    roastLevel: 'Light',
    notes: ['Jasmine Blossom', 'Bergamot', 'White Peach', 'Wild Honey'],
    price: 6.25,
    image: 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80',
    description: 'A luminous, tea-like cup with explosive floral aroma and sweet stone-fruit brightness. Hand-dripped through custom Kalita Wave ceramic drippers with unbleached organic paper filters.',
    allergenTags: [
      { label: 'Organic Certified', type: 'matcha' },
      { label: 'Single Origin', type: 'matcha' },
      { label: 'High Caffeine', type: 'roast' }
    ],
    badge: 'Single Origin',
    isDrink: true,
    brewRatioRecommendation: '1:16.5 ratio (18g coffee to 300g water @ 93°C)',
    sensoryScores: {
      aroma: 95,
      acidity: 92,
      body: 80,
      sweetness: 94,
      finish: 91
    }
  },
  {
    id: 'panama-geisha',
    name: 'Panama Boquete Geisha Reserve',
    category: 'pourover',
    origin: 'Boquete, Chiriquí, Panama',
    farm: 'Hacienda La Esmeralda',
    elevation: '1,850 MASL',
    process: 'Slow Anaerobic Washed',
    roastLevel: 'Light',
    notes: ['Meyer Lemon', 'Orange Blossom', 'Dried Apricot', 'Honeysuckle'],
    price: 8.50,
    image: 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80',
    description: 'The pinnacle of floral elegance. Boasts a crystal-clean mouthfeel reminiscent of Earl Grey tea with notes of candied citrus and delicate jasmine blossom.',
    allergenTags: [
      { label: 'Micro-Lot', type: 'matcha' },
      { label: 'Direct Trade', type: 'matcha' }
    ],
    badge: 'Micro-Lot',
    isDrink: true,
    brewRatioRecommendation: '1:17 ratio (16g coffee to 272g water @ 92°C)',
    sensoryScores: {
      aroma: 98,
      acidity: 96,
      body: 78,
      sweetness: 95,
      finish: 96
    }
  },
  {
    id: 'velvet-cortado',
    name: 'Artisan Velvet Cortado',
    category: 'espresso',
    origin: 'Colombia Huila & Ethiopia Sidama Blend',
    farm: 'Finca El Paraiso & Bensa Smallholders',
    elevation: '1,950 MASL',
    process: 'Double Fermented Washed',
    roastLevel: 'Medium-Light',
    notes: ['Roasted Hazelnut', 'Dark Cacao', 'Brown Butter', 'Toffee'],
    price: 5.25,
    image: 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=800&q=80',
    description: 'Equal parts velvety double espresso and silky micro-textured steamed milk, served in an unglazed ceramic Gibraltar cup. Balanced, concentrated, and comforting.',
    allergenTags: [
      { label: 'Oat Milk Available', type: 'matcha' },
      { label: 'Contains Dairy', type: 'roast' }
    ],
    badge: 'Barista Choice',
    isDrink: true,
    sensoryScores: {
      aroma: 90,
      acidity: 82,
      body: 95,
      sweetness: 90,
      finish: 88
    }
  },
  {
    id: 'cardamom-honey-latte',
    name: 'Spiced Cardamom & Honey Latte',
    category: 'espresso',
    origin: 'Guatemala Antigua Single Origin',
    elevation: '1,600 MASL',
    process: 'Fully Washed',
    roastLevel: 'Medium-Light',
    notes: ['Green Cardamom', 'Wildflower Honey', 'Cinnamon Bark', 'Almond'],
    price: 6.75,
    image: 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=800&q=80',
    description: 'House-crushed green cardamom pods simmered with local wildflower honey and folded into micro-foamed oat milk with a rich double shot of espresso.',
    allergenTags: [
      { label: 'House Signature', type: 'matcha' },
      { label: 'Dairy-Free Base', type: 'matcha' }
    ],
    badge: 'Seasonal',
    isDrink: true,
    sensoryScores: {
      aroma: 96,
      acidity: 75,
      body: 92,
      sweetness: 92,
      finish: 94
    }
  },
  {
    id: 'kyoto-cold-brew',
    name: '24-Hour Kyoto Slow-Drip Reserve',
    category: 'cold-nitro',
    origin: 'Sumatra Kerinci & Colombia Popayan',
    elevation: '1,700 MASL',
    process: 'Wet Hulled & Washed',
    roastLevel: 'Medium',
    notes: ['Black Cherry', 'Cacao Nibs', 'Bourbon Vanilla', 'Pipe Tobacco'],
    price: 6.00,
    image: 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=800&q=80',
    description: 'Crafted drop-by-drop through towering hand-blown glass Kyoto towers over 24 continuous hours. Extraordinarily low acidity with deep, liqueur-like chocolate depth.',
    allergenTags: [
      { label: 'Single Batch', type: 'matcha' },
      { label: 'High Caffeine', type: 'roast' }
    ],
    badge: 'Seasonal',
    isDrink: true,
    sensoryScores: {
      aroma: 89,
      acidity: 68,
      body: 98,
      sweetness: 90,
      finish: 95
    }
  },
  {
    id: 'nitro-draft-latte',
    name: 'Nitro Oat Draft Flat White',
    category: 'cold-nitro',
    origin: 'East African Roastery Blend',
    elevation: '1,900 MASL',
    process: 'Washed',
    roastLevel: 'Medium-Light',
    notes: ['Sweet Cream', 'Caramelized Toffee', 'Praline', 'Malt'],
    price: 6.50,
    image: 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=800&q=80',
    description: 'Poured ice-cold on nitrogen draft. Infused with micro-bubbles producing a cascading Guinness-like crema head and a naturally sweet, dairy-free texture without ice.',
    allergenTags: [
      { label: '100% Oat Milk', type: 'matcha' },
      { label: 'Vegan Friendly', type: 'matcha' }
    ],
    badge: 'Barista Choice',
    isDrink: true,
    sensoryScores: {
      aroma: 88,
      acidity: 72,
      body: 96,
      sweetness: 93,
      finish: 90
    }
  },
  {
    id: 'colombia-pink-bourbon-beans',
    name: 'Colombia Huila Pink Bourbon (250g)',
    category: 'whole-bean',
    origin: 'San Adolfo, Huila, Colombia',
    farm: 'Finca La Cereza · Producer Rodrigo Sanchez',
    elevation: '1,750 MASL',
    process: 'Cold Anaerobic Washed (76 hrs)',
    roastLevel: 'Medium-Light',
    notes: ['Pink Grapefruit', 'Papaya', 'Sugar Cane', 'Red Currant'],
    price: 22.00,
    image: 'https://images.unsplash.com/photo-1587734195503-904fca47e0e9?auto=format&fit=crop&w=800&q=80',
    description: 'A prized rare botanical variety known for its naturally pink coffee cherries. Clean acidity with succulent tropical fruit notes and lingering sweetness. Roasted in small 5kg batches.',
    allergenTags: [
      { label: 'Whole Bean Roasted Fresh', type: 'matcha' },
      { label: 'Micro-Lot', type: 'matcha' }
    ],
    badge: 'Micro-Lot',
    isDrink: false,
    brewRatioRecommendation: '1:16 pour over ratio (20g ground to 320g water)',
    sensoryScores: {
      aroma: 97,
      acidity: 94,
      body: 84,
      sweetness: 96,
      finish: 93
    }
  },
  {
    id: 'guatemala-antigua-beans',
    name: 'Guatemala Finca Medina Volcanic (250g)',
    category: 'whole-bean',
    origin: 'Antigua Valley, Guatemala',
    farm: 'Finca Medina Shade-Grown Estate',
    elevation: '1,650 MASL',
    process: 'Eco-Washed, Patio Dried',
    roastLevel: 'Medium',
    notes: ['Dark Chocolate Ganache', 'Candied Orange', 'Toasted Almond'],
    price: 19.50,
    image: 'https://images.unsplash.com/photo-1611854779393-1b2da9d400fe?auto=format&fit=crop&w=800&q=80',
    description: 'Nourished by rich volcanic soil from Agua and Fuego volcanoes. Deep comforting cocoa richness balanced by pleasant citrus zest. Superb for espresso and French press.',
    allergenTags: [
      { label: 'Rainforest Alliance', type: 'matcha' },
      { label: 'Contains Nut Notes', type: 'roast' }
    ],
    badge: 'Single Origin',
    isDrink: false,
    brewRatioRecommendation: '1:15 ratio for immersion brews',
    sensoryScores: {
      aroma: 91,
      acidity: 81,
      body: 93,
      sweetness: 89,
      finish: 92
    }
  },
  {
    id: 'cardamom-morning-bun',
    name: 'Artisanal Cardamom Morning Bun',
    category: 'pastries',
    origin: 'Baked in-house daily',
    notes: ['Laminated Sourdough', 'Swedish Cardamom', 'Pearl Sugar', 'Normandy Butter'],
    price: 5.50,
    image: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80',
    description: 'Wild yeast laminated brioche coiled with freshly stone-ground organic green cardamom, brown sugar, and sea salt, topped with Scandinavian pearl sugar.',
    allergenTags: [
      { label: 'Fresh Daily 6AM', type: 'matcha' },
      { label: 'Contains Wheat & Dairy', type: 'roast' }
    ],
    badge: 'Fresh Baked',
    isDrink: false
  },
  {
    id: 'valrhona-pain-chocolat',
    name: 'Valrhona 70% Pain au Chocolat',
    category: 'pastries',
    origin: 'Baked in-house daily',
    notes: ['Double Guanaja Batons', 'Cultured Butter', 'Flaky 27-Layer Fold'],
    price: 5.25,
    image: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80',
    description: 'Folded with 84% butterfat cultured butter and double batons of pure dark Valrhona Guanaja chocolate. Crispy golden exterior that shatters into a soft honeycomb crumb.',
    allergenTags: [
      { label: 'French Artisanal Butter', type: 'matcha' },
      { label: 'Contains Wheat & Dairy', type: 'roast' }
    ],
    badge: 'Fresh Baked',
    isDrink: false
  }
];

export const INITIAL_ORDER_DATA = {
  orderId: '#AT-2849',
  customerName: 'Elena Rostova',
  pickupCounter: 'COUNTER #B-04',
  lockerPin: '8249',
  placedTime: '10:42 AM',
  estimatedReadyTime: '10:52 AM',
  stage: 'brewing' as const,
  baristaName: 'Lucas & Sofia',
  baristaNote: 'Grinding single-origin Ethiopia Konga & texturing Oatly Barista microfoam at 65°C.',
  items: [
    {
      cartItemId: 'item-demo-1',
      product: COFFEE_PRODUCTS[3], // Cardamom honey latte
      customization: {
        size: '12oz' as const,
        milk: 'Oatly Barista' as const,
        temp: 'Hot (68°C)' as const,
        grind: 'Espresso Fine' as const,
        syrup: 'Spiced Cardamom Honey (+0.75)' as const,
        shots: 'Double (Standard)' as const,
        notes: 'Extra cardamom dusting on top please!'
      },
      quantity: 1,
      unitPrice: 7.50,
      totalPrice: 7.50
    },
    {
      cartItemId: 'item-demo-2',
      product: COFFEE_PRODUCTS[8], // Cardamom bun
      customization: {
        size: '8oz' as const,
        milk: 'None' as const,
        temp: 'Warm (60°C)' as const,
        grind: 'Whole Bean' as const,
        syrup: 'None' as const,
        shots: 'Double (Standard)' as const,
        notes: 'Warmed gently'
      },
      quantity: 1,
      unitPrice: 5.50,
      totalPrice: 5.50
    }
  ],
  subtotal: 13.00,
  tip: 2.34,
  tax: 1.14,
  total: 16.48
};
