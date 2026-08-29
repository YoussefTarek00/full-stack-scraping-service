export type Product = {
  id: number;
  title: string;
  price: string;
  image_url: string;
  created_at: string;
};

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";

export async function fetchProducts(): Promise<Product[]> {
  const response = await fetch(`${API_BASE_URL}/api/products`, {
    cache: "no-store",
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch products (status ${response.status})`);
  }

  const payload = (await response.json()) as { data: Product[] };

  return payload.data;
}
