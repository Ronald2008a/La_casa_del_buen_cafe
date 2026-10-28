import React, { useState } from 'react';
import { CartItem } from '../types/coffee';
import { X, Plus, Minus, Trash2, ArrowRight, Clock, MapPin, Sparkles, ShieldCheck } from 'lucide-react';

interface CartDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  items: CartItem[];
  onUpdateQuantity: (cartItemId: string, newQty: number) => void;
  onRemoveItem: (cartItemId: string) => void;
  onCheckout: (tipAmount: number, customerName: string) => void;
}

export const CartDrawer: React.FC<CartDrawerProps> = ({
  isOpen,
  onClose,
  items,
  onUpdateQuantity,
  onRemoveItem,
  onCheckout
}) => {
  const [tipPercentage, setTipPercentage] = useState<number>(18);
  const [customName, setCustomName] = useState('Elena Rostova');

  if (!isOpen) return null;

  const subtotal = items.reduce((acc, item) => acc + item.totalPrice, 0);
  const tax = subtotal * 0.0875;
  const tip = (subtotal * tipPercentage) / 100;
  const total = subtotal + tax + tip;

  const handleCheckoutClick = () => {
    if (items.length === 0) return;
    onCheckout(tip, customName || 'Guest Patron');
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden bg-black/40 backdrop-blur-xs flex justify-end">
      {/* Tap backdrop to close */}
      <div className="fixed inset-0" onClick={onClose} />

      <aside className="relative w-full max-w-md bg-[#FFF8F6] h-full shadow-2xl flex flex-col z-10 border-l border-[#EFE8E1] animate-in slide-in-from-right duration-200">
        
        {/* Drawer Header */}
        <div className="px-6 py-4 border-b border-[#EFE8E1] bg-[#FFF8F6] flex items-center justify-between">
          <div className="flex items-center gap-2">
            <h2 className="font-heading text-lg font-bold text-[#211A18]">
              Your Roastery Order
            </h2>
            <span className="text-xs bg-[#FAEDE8] text-[#94452C] font-semibold px-2 py-0.5 rounded-full border border-[#EFE8E1]">
              {items.reduce((acc, item) => acc + item.quantity, 0)} items
            </span>
          </div>

          <button
            onClick={onClose}
            aria-label="Close cart"
            className="w-8 h-8 rounded-full bg-[#FAEDE8] text-[#211A18] hover:bg-[#F4E3DD] flex items-center justify-center transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Pickup Location & Estimated Ready Time Strip */}
        <div className="bg-[#FFFFFF] border-b border-[#EFE8E1] px-6 py-3 flex items-center justify-between text-xs text-[#55433E]">
          <div className="flex items-center gap-2">
            <MapPin className="w-4 h-4 text-[#94452C] shrink-0" />
            <span className="font-medium text-[#211A18]">Downtown Roastery Bar</span>
          </div>
          <div className="flex items-center gap-1.5 text-[#4E6E58] font-semibold">
            <Clock className="w-3.5 h-3.5" />
            <span>Ready in ~8–12 min</span>
          </div>
        </div>

        {/* Items list */}
        <div className="flex-1 overflow-y-auto px-6 py-4 space-y-4">
          {items.length === 0 ? (
            <div className="h-64 flex flex-col items-center justify-center text-center p-6">
              <div className="w-14 h-14 rounded-full bg-[#FAEDE8] text-[#94452C] flex items-center justify-center mb-3">
                <Sparkles className="w-6 h-6" />
              </div>
              <p className="font-heading font-semibold text-[#211A18] text-base">
                Your order bag is empty
              </p>
              <p className="text-xs text-[#795746] mt-1 max-w-xs">
                Explore our single origin pour overs, signature velvet espresso brews, and fresh morning pastries.
              </p>
            </div>
          ) : (
            items.map((item) => (
              <div
                key={item.cartItemId}
                className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl p-4 shadow-xs flex flex-col gap-3"
              >
                <div className="flex gap-3">
                  <img
                    src={item.product.image}
                    alt={item.product.name}
                    className="w-16 h-16 rounded-xl object-cover border border-[#EFE8E1] shrink-0"
                  />
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <h3 className="font-heading font-bold text-sm text-[#211A18] truncate">
                        {item.product.name}
                      </h3>
                      <button
                        onClick={() => onRemoveItem(item.cartItemId)}
                        aria-label="Remove item"
                        className="text-[#88726C] hover:text-[#BA1A1A] transition-colors p-0.5"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>

                    {/* Customization Recipe Summary */}
                    <div className="text-[11px] text-[#795746] mt-1 line-clamp-2 leading-relaxed">
                      {item.product.isDrink && (
                        <span>{item.customization.size} · {item.customization.milk} · {item.customization.temp}</span>
                      )}
                      {item.customization.syrup !== 'None' && (
                        <span> · {item.customization.syrup.replace(' (+0.75)', '')}</span>
                      )}
                      {item.customization.notes && (
                        <div className="text-[#94452C] italic mt-0.5">
                          "{item.customization.notes}"
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Card footer: Stepper in pill-shaped container and price */}
                <div className="pt-2 border-t border-[#EFE8E1] flex items-center justify-between">
                  <div className="inline-flex items-center bg-[#FAEDE8] border border-[#EFE8E1] rounded-full p-0.5">
                    <button
                      onClick={() => onUpdateQuantity(item.cartItemId, item.quantity - 1)}
                      className="w-7 h-7 rounded-full flex items-center justify-center text-[#5C3D2E] hover:bg-[#FFFFFF] transition-colors"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-7 text-center font-bold text-xs text-[#211A18] tabular-nums">
                      {item.quantity}
                    </span>
                    <button
                      onClick={() => onUpdateQuantity(item.cartItemId, item.quantity + 1)}
                      className="w-7 h-7 rounded-full flex items-center justify-center text-[#5C3D2E] hover:bg-[#FFFFFF] transition-colors"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>

                  <span className="font-heading font-bold text-sm text-[#211A18] tabular-nums">
                    ${item.totalPrice.toFixed(2)}
                  </span>
                </div>
              </div>
            ))
          )}

          {items.length > 0 && (
            <div className="bg-[#FFFFFF] border border-[#EFE8E1] rounded-2xl p-4 space-y-3">
              <div>
                <label className="block text-xs font-semibold text-[#211A18] mb-1">
                  Patron Name for Pickup Counter
                </label>
                <input
                  type="text"
                  value={customName}
                  onChange={(e) => setCustomName(e.target.value)}
                  placeholder="Your Name"
                  className="w-full h-10 px-3 rounded-xl bg-[#FFF8F6] border border-[#EFE8E1] text-xs text-[#211A18] focus:outline-none focus:border-[#94452C]"
                />
              </div>

              {/* Barista Tip Selector */}
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-semibold text-[#211A18]">
                    Support Our Baristas
                  </span>
                  <span className="text-xs text-[#795746] font-mono">
                    ${tip.toFixed(2)}
                  </span>
                </div>
                <div className="grid grid-cols-4 gap-1.5">
                  {[15, 18, 20, 0].map((pct) => (
                    <button
                      key={pct}
                      type="button"
                      onClick={() => setTipPercentage(pct)}
                      className={`py-1.5 rounded-lg text-xs font-semibold border transition-all ${
                        tipPercentage === pct
                          ? 'bg-[#94452C] text-white border-[#94452C]'
                          : 'bg-[#FFF8F6] text-[#55433E] border-[#EFE8E1] hover:border-[#dbc1ba]'
                      }`}
                    >
                      {pct === 0 ? 'None' : `${pct}%`}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Footer Checkout Summary */}
        {items.length > 0 && (
          <div className="p-5 border-t border-[#EFE8E1] bg-[#FFF8F6] space-y-3">
            <div className="space-y-1.5 text-xs text-[#55433E]">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span className="tabular-nums font-medium text-[#211A18]">${subtotal.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Estimated Tax (8.75%)</span>
                <span className="tabular-nums font-medium text-[#211A18]">${tax.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Barista Gratuity</span>
                <span className="tabular-nums font-medium text-[#211A18]">${tip.toFixed(2)}</span>
              </div>
              <div className="pt-2 border-t border-[#EFE8E1] flex justify-between text-sm font-bold text-[#211A18]">
                <span>Total Due</span>
                <span className="font-heading text-lg font-bold text-[#94452C] tabular-nums">
                  ${total.toFixed(2)}
                </span>
              </div>
            </div>

            <button
              onClick={handleCheckoutClick}
              className="w-full h-[52px] rounded-full bg-[#94452C] hover:bg-[#803B25] text-white font-semibold text-sm flex items-center justify-between px-6 active:scale-[0.98] transition-all shadow-sm"
            >
              <span>Send Order to Roastery</span>
              <div className="flex items-center gap-1.5">
                <span className="font-heading font-bold tabular-nums">${total.toFixed(2)}</span>
                <ArrowRight className="w-4 h-4" />
              </div>
            </button>

            <div className="flex items-center justify-center gap-1 text-[11px] text-[#88726C]">
              <ShieldCheck className="w-3.5 h-3.5 text-[#4E6E58]" />
              <span>Contactless pickup at Station #B-04</span>
            </div>
          </div>
        )}

      </aside>
    </div>
  );
};
