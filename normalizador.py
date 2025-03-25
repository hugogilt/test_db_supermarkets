from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np

# Extraer nombres limpios
mercadona_names = [product["clean_name"] for product in mercadona_products]
consum_names = [product["clean_name"] for product in consum_products]

# Vectorizar los nombres de productos
vectorizer = TfidfVectorizer().fit(mercadona_names + consum_names)
mercadona_vectors = vectorizer.transform(mercadona_names)
consum_vectors = vectorizer.transform(consum_names)

# Calcular similitudes de coseno entre productos
similarity_matrix = cosine_similarity(mercadona_vectors, consum_vectors)

# Buscar mejores coincidencias
threshold = 0.7  # Umbral ajustado para similitud
matched_products = []

for i, mercadona_product in enumerate(mercadona_products):
    best_match_idx = np.argmax(similarity_matrix[i])  # Índice del producto más similar
    best_score = similarity_matrix[i][best_match_idx]

    if best_score >= threshold:
        best_match = consum_products[best_match_idx]
        matched_products.append({
            "mercadona": mercadona_product["nombre"],
            "consum": best_match["name"],
            "similarity": best_score
        })

# Mostrar algunos ejemplos
matched_products[:10]
