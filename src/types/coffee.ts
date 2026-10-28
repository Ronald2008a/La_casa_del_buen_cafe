export type Category = 
  | 'all'
  | 'espresso'
  | 'pourover'
  | 'cold-nitro'
  | 'whole-bean'
  | 'pastries';

export interface Product {
  id: string;
  name: string;
  category: Category;
  origin: string;
  farm?: string;
  elevation?: string;
  process?: string;
  roastLevel?: 'Light' | 'Medium-Light' | 'Medium' | 'Dark';
  notes: string[];
  price: number;
  image: string;
  description: string;
  allergenTags: Array<{
    label: string;
    type: 'matcha' | 'roast'; // matcha green for organic/oat/dairy-free, roast brown for nuts/gluten/caffeine
  }>;
  badge?: 'Single Origin' | 'Seasonal' | 'Barista Choice' | 'Micro-Lot' | 'Fresh Baked';
  isDrink: boolean;
  brewRatioRecommendation?: string;
  sensoryScores?: {
    aroma: number;
    acidity: number;
    body: number;
    sweetness: number;
    finish: number;
  };
}

export interface CustomizationOptions {
  size: '8oz' | '12oz' | '16oz';
  milk: 'Oatly Barista' | 'Organic Whole Milk' | 'House Almond' | 'Macadamia (+0.75)' | 'None';
  temp: 'Warm (60°C)' | 'Hot (68°C)' | 'Extra Hot (75°C)' | 'Iced Normal' | 'Iced Light';
  grind: 'Whole Bean' | 'Chemex' | 'V60 Pour Over' | 'Aeropress' | 'Espresso Fine' | 'French Press';
  syrup: 'None' | 'Madagascar Bourbon Vanilla (+0.75)' | 'Spiced Cardamom Honey (+0.75)' | 'Lavender Blossom (+0.75)' | 'Salted Brown Sugar (+0.75)';
  shots: 'Double (Standard)' | 'Single Shot' | 'Triple (+1.00)' | 'Decaf Swiss Water';
  notes: string;
}

export interface CartItem {
  cartItemId: string;
  product: Product;
  customization: CustomizationOptions;
  quantity: number;
  unitPrice: number;
  totalPrice: number;
}

export type OrderStage = 'received' | 'brewing' | 'ready';

export interface LiveOrder {
  orderId: string;
  customerName: string;
  pickupCounter: string;
  lockerPin: string;
  placedTime: string;
  estimatedReadyTime: string;
  stage: OrderStage;
  baristaNote: string;
  baristaName: string;
  items: CartItem[];
  subtotal: number;
  tip: number;
  tax: number;
  total: number;
}
