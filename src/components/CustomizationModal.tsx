import React, { useState } from 'react';
import { Product, CustomizationOptions } from '../types/coffee';
import { X, Plus, Minus, Check, Flame, Droplet, Coffee, Sparkles } from 'lucide-react';

interface CustomizationModalProps {
  product: Product | null;
  isOpen: boolean;
  onClose: () => void;
  onAddToCart: (product: Product, options: CustomizationOptions, quantity: number, unitPrice: number) => void;
}

export const CustomizationModal: React.FC<CustomizationModalProps> = ({
  product,
  isOpen,
  onClose,
  onAddToCart
}) => {
  if (!isOpen || !product) return null;

  const [quantity, setQuantity] = useState(1);
  const [size, setSize] = useState<'8oz' | '12oz' | '16oz'>('12oz');
  const [milk, setMilk] = useState<CustomizationOptions['milk']>('Oatly Barista');
  const [temp, setTemp] = useState<CustomizationOptions['temp']>('Hot (68°C)');
  const [grind, setGrind] = useState<CustomizationOptions['grind']>(
    product.isDrink ? 'Espresso Fine' : 'Whole Bean'
  );
  const [syrup, setSyrup] = useState<CustomizationOptions['syrup']>('None');
  const [shots, setShots] = useState<CustomizationOptions['shots']>('Double (Standard)');
  const [notes, setNotes] = useState('');

  // Calculate dynamic price
  const calculateUnitPrice = (): number => {
    let price = product.price;
    // Size adjustments
    if (product.isDrink) {
      if (size === '8oz') price -= 0.50;
      if (size === '16oz') price += 0.75;
    }
    // Milk extras
    if (milk.includes('+0.75')) price += 0.75;
    // Syrup extras
    if (syrup.includes('+0.75')) price += 0.75;
    // Shot extras
    if (shots.includes('+1.00')) price += 1.00;

    return Math.max(price, product.price * 0.8);
  };

  const unitPrice = calculateUnitPrice();
  const totalPrice = unitPrice * quantity;

  const handleAdd = () => {
    onAddToCart(
      product,
      { size, milk, temp, grind, syrup, shots, notes },
      quantity,
      unitPrice
    );
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
      {/* Backdrop tap to close */}
      <div className="fixed inset-0" onClick={onClose} />

      {/* Modal / Bottom Sheet Container with elevated depth */}
      <div 
        className="relative w-full max-w-xl bg-[#FFF8F6] rounded-t-3xl sm:rounded-3xl shadow-sheet-elevation z-10 max-h-[92vh] flex flex-col border border-[#EFE8E1] overflow-hidden animate-in slide-in-from-bottom-6 duration-200"
      >
        {/* Header Bar */}
        <div className="sticky top-0 bg-[#FFF8F6]/95 backdrop-blur-md px-6 py-4 border-b border-[#EFE8E1] flex items-center justify-between z-20">
          <div className="flex items-center gap-2">
            <span className="text-xs uppercase tracking-wider font-semibold text-[#88726C]">
              Customizing Recipe
            </span>
          </div>
          <button
            onClick={onClose}
            aria-label="Close modal"
            className="w-9 h-9 rounded-full bg-[#FAEDE8] text-[#211A18] hover:bg-[#F4E3DD] flex items-center justify-center transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Scrollable Content */}
        <div className="overflow-y-auto px-6 py-5 space-y-6">
          {/* Product Overview Lockup */}
          <div className="flex gap-4 items-start">
            <img
              src={product.image}
              alt={product.name}
              className="w-20 h-20 rounded-xl object-cover border border-[#EFE8E1] shrink-0"
            />
            <div>
              <h2 className="font-heading text-xl font-bold text-[#211A18] leading-tight">
                {product.name}
              </h2>
              <p className="text-xs text-[#795746] mt-1 font-medium">
                {product.origin}
              </p>
              <p className="text-xs text-[#55433E] mt-1 line-clamp-2">
                {product.description}
              </p>
              <div className="mt-2 text-xs text-[#94452C] font-semibold">
                Tasting Notes: {product.notes.join(' · ')}
              </div>
            </div>
          </div>

          {/* If it's a drink: Size Selection */}
          {product.isDrink && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                Cup Volume & Proportion
              </label>
              <div className="grid grid-cols-3 gap-2.5">
                {[
                  { id: '8oz', label: '8 oz', sub: 'Cortado / Traditional' },
                  { id: '12oz', label: '12 oz', sub: 'Standard Artisan' },
                  { id: '16oz', label: '16 oz', sub: 'Reserve Grande' }
                ].map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => setSize(item.id as any)}
                    className={`p-3 rounded-xl text-left border transition-all flex flex-col justify-between ${
                      size === item.id
                        ? 'border-[#94452C] bg-[#FAEDE8] ring-2 ring-[#94452C]/30 shadow-xs'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span className="font-heading font-bold text-sm text-[#211A18]">
                      {item.label}
                    </span>
                    <span className="text-[11px] text-[#795746] mt-1">
                      {item.sub}
                    </span>
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Temperature & Ice Control */}
          {product.isDrink && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                Brew Temperature / Ice
              </label>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                {[
                  'Warm (60°C)',
                  'Hot (68°C)',
                  'Extra Hot (75°C)',
                  'Iced Normal',
                  'Iced Light'
                ].map((tempOption) => (
                  <button
                    key={tempOption}
                    type="button"
                    onClick={() => setTemp(tempOption as any)}
                    className={`px-3 py-2.5 rounded-xl text-xs font-medium border text-left flex items-center justify-between transition-all ${
                      temp === tempOption
                        ? 'border-[#94452C] bg-[#FAEDE8] text-[#94452C] ring-1 ring-[#94452C]'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] text-[#211A18] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span>{tempOption}</span>
                    {temp === tempOption && <Check className="w-3.5 h-3.5 text-[#94452C]" />}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Milk Texture & Alternative Dairy */}
          {product.isDrink && product.category !== 'pourover' && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                Milk & Textured Microfoam
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {[
                  { id: 'Oatly Barista', label: 'Oatly Barista Edition (Default)' },
                  { id: 'Organic Whole Milk', label: 'Organic Jersey Whole Milk' },
                  { id: 'House Almond', label: 'House Sprouted Almond Milk' },
                  { id: 'Macadamia (+0.75)', label: 'Artisan Macadamia (+0.75)' },
                  { id: 'None', label: 'No Milk (Pure Black)' }
                ].map((milkOption) => (
                  <button
                    key={milkOption.id}
                    type="button"
                    onClick={() => setMilk(milkOption.id as any)}
                    className={`p-3 rounded-xl text-left border transition-all flex items-center justify-between ${
                      milk === milkOption.id
                        ? 'border-[#94452C] bg-[#FAEDE8] text-[#211A18] ring-1 ring-[#94452C]'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] text-[#55433E] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span className="text-xs font-medium">{milkOption.label}</span>
                    {milk === milkOption.id && <Check className="w-3.5 h-3.5 text-[#94452C]" />}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Espresso Extraction / Shots */}
          {product.category === 'espresso' && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                Espresso Extraction
              </label>
              <div className="grid grid-cols-2 gap-2">
                {[
                  { id: 'Double (Standard)', label: 'Double Shot (Standard 18g:36g)' },
                  { id: 'Single Shot', label: 'Single Shot (Light extraction)' },
                  { id: 'Triple (+1.00)', label: 'Triple Shot (+1.00)' },
                  { id: 'Decaf Swiss Water', label: 'Decaf Swiss Water Single Origin' }
                ].map((shotOption) => (
                  <button
                    key={shotOption.id}
                    type="button"
                    onClick={() => setShots(shotOption.id as any)}
                    className={`p-2.5 rounded-xl text-left border text-xs font-medium transition-all flex items-center justify-between ${
                      shots === shotOption.id
                        ? 'border-[#94452C] bg-[#FAEDE8] text-[#94452C] ring-1 ring-[#94452C]'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] text-[#211A18] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span>{shotOption.label}</span>
                    {shots === shotOption.id && <Check className="w-3.5 h-3.5 text-[#94452C]" />}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* House Infused Artisanal Syrups */}
          {product.isDrink && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                House Infused Botanical & Spiced Syrups
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {[
                  { id: 'None', label: 'Unsweetened (Pure Coffee Notes)' },
                  { id: 'Spiced Cardamom Honey (+0.75)', label: 'Spiced Cardamom Honey (+0.75)' },
                  { id: 'Madagascar Bourbon Vanilla (+0.75)', label: 'Madagascar Bourbon Vanilla (+0.75)' },
                  { id: 'Lavender Blossom (+0.75)', label: 'Organic Lavender Blossom (+0.75)' }
                ].map((syrupOption) => (
                  <button
                    key={syrupOption.id}
                    type="button"
                    onClick={() => setSyrup(syrupOption.id as any)}
                    className={`p-2.5 rounded-xl text-left border text-xs font-medium transition-all flex items-center justify-between ${
                      syrup === syrupOption.id
                        ? 'border-[#94452C] bg-[#FAEDE8] text-[#94452C] ring-1 ring-[#94452C]'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] text-[#211A18] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span>{syrupOption.label}</span>
                    {syrup === syrupOption.id && <Check className="w-3.5 h-3.5 text-[#94452C]" />}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Grind Selection (For whole beans or pour over) */}
          {(!product.isDrink || product.category === 'pourover') && (
            <div>
              <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
                Grind Setting (Mahlkönig EK43 Calibrated)
              </label>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                {[
                  'Whole Bean',
                  'Chemex',
                  'V60 Pour Over',
                  'Aeropress',
                  'Espresso Fine',
                  'French Press'
                ].map((grindOption) => (
                  <button
                    key={grindOption}
                    type="button"
                    onClick={() => setGrind(grindOption as any)}
                    className={`p-2.5 rounded-xl text-left border text-xs font-medium transition-all flex items-center justify-between ${
                      grind === grindOption
                        ? 'border-[#94452C] bg-[#FAEDE8] text-[#94452C] ring-1 ring-[#94452C]'
                        : 'border-[#EFE8E1] bg-[#FFFFFF] text-[#211A18] hover:border-[#dbc1ba]'
                    }`}
                  >
                    <span>{grindOption}</span>
                    {grind === grindOption && <Check className="w-3.5 h-3.5 text-[#94452C]" />}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Barista Instruction Note per spec:
              "Text inputs: 48px height, Oat Milk background, espresso placeholder text (#8A7B75), focusing to a 1.5px Terracotta Amber border." */}
          <div>
            <label className="block text-xs font-bold text-[#211A18] uppercase tracking-wider mb-2">
              Note for Barista
            </label>
            <input
              type="text"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="e.g. Extra hot, light ice, warm pastry, cardamom dusting..."
              className="w-full h-12 px-4 rounded-xl bg-[#FFF8F6] border border-[#EFE8E1] text-sm text-[#211A18] placeholder-[#8A7B75] focus:outline-none focus:border-[#94452C] focus:ring-1 focus:ring-[#94452C] transition-colors"
            />
          </div>
        </div>

        {/* Modal Sticky Footer with Quantity Modifier & 52px Primary Pill CTA */}
        <div className="sticky bottom-0 bg-[#FFF8F6]/95 backdrop-blur-md p-4 sm:p-5 border-t border-[#EFE8E1] flex items-center justify-between gap-4">
          
          {/* Quantity Stepper inside pill container per spec */}
          <div className="inline-flex items-center bg-[#FAEDE8] border border-[#EFE8E1] rounded-full p-1 shadow-xs">
            <button
              type="button"
              onClick={() => setQuantity((q) => Math.max(1, q - 1))}
              aria-label="Decrease quantity"
              className="w-9 h-9 rounded-full flex items-center justify-center text-[#5C3D2E] hover:bg-[#FFFFFF] transition-colors active:scale-95"
            >
              <Minus className="w-4 h-4" />
            </button>
            <span className="w-9 text-center font-bold text-sm text-[#211A18] tabular-nums">
              {quantity}
            </span>
            <button
              type="button"
              onClick={() => setQuantity((q) => q + 1)}
              aria-label="Increase quantity"
              className="w-9 h-9 rounded-full flex items-center justify-center text-[#5C3D2E] hover:bg-[#FFFFFF] transition-colors active:scale-95"
            >
              <Plus className="w-4 h-4" />
            </button>
          </div>

          {/* Primary CTA (52px thumb target, Terracotta Amber #94452C, 0.98 active scale) */}
          <button
            type="button"
            onClick={handleAdd}
            className="flex-1 h-[52px] px-6 rounded-full bg-[#94452C] hover:bg-[#803B25] text-white font-semibold text-sm sm:text-base flex items-center justify-between shadow-sm active:scale-[0.98] transition-all"
          >
            <span>Add to Order</span>
            <span className="font-heading font-bold tabular-nums">
              ${totalPrice.toFixed(2)}
            </span>
          </button>
        </div>

      </div>
    </div>
  );
};
