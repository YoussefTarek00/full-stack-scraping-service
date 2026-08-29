"use client";

import { useProducts } from "@/hooks/useProducts";
import { ProductGrid } from "@/components/ProductGrid";

export default function ProductsPage() {
  const { products, isLoading, error } = useProducts();

  return (
    <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-6 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
        Products
      </h1>

      {isLoading && products.length === 0 && (
        <p className="text-zinc-500 dark:text-zinc-400">Loading products…</p>
      )}

      {error && (
        <p className="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
          {error}
        </p>
      )}

      {!isLoading && !error && products.length === 0 && (
        <p className="text-zinc-500 dark:text-zinc-400">No products found.</p>
      )}

      {products.length > 0 && <ProductGrid products={products} />}
    </main>
  );
}
