<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Search with Pagination</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 600;
        }

        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 8px;
            border-radius: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-group {
            display: flex;
            align-items: center;
            flex: 1;
            padding: 0 16px;
        }

        .search-group svg {
            color: #666;
            width: 20px;
            height: 20px;
            margin-right: 12px;
        }

        .search-input {
            width: 100%;
            padding: 12px 0;
            font-size: 16px;
            border: none;
            background: none;
        }

        .search-button {
            padding: 12px 32px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 16px;
        }

        .search-button:hover {
            background-color: #2980b9;
        }

        .card-list {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            min-height: 200px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .card p {
            margin-bottom: 25px;
            color: #7f8c8d;
        }

        .select-button {
            position: absolute;
            bottom: 25px;
            right: 25px;
            padding: 12px 24px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .select-button:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            gap: 10px;
        }

        .pagination button {
            padding: 10px 15px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .pagination button:hover {
            background-color: #2980b9;
        }

        .pagination button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Which table are you looking for?</h1>
        
        <div class="search-container">
            <div class="search-group">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" placeholder="Search tables..." class="search-input">
            </div>
            <button class="search-button">Search</button>
        </div>
        
        <div class="card-list" id="cardList">
            <!-- Cards will be dynamically inserted here -->
        </div>

        <div class="pagination">
            <button id="prevPage" disabled>Previous</button>
            <button id="nextPage">Next</button>
        </div>
    </div>

    <script>
        const tables = [
            { name: "Users", description: "Contains user information including profiles, preferences, and account details." },
            { name: "Products", description: "Comprehensive list of all products with descriptions, pricing, and inventory status." },
            { name: "Orders", description: "Detailed customer order information including items, quantities, and shipping details." },
            { name: "Inventory", description: "Real-time stock levels, reorder points, and supplier information for all products." },
            { name: "Customers", description: "Comprehensive customer contact information, purchase history, and preferences." },
            { name: "Suppliers", description: "Detailed information about suppliers, including contact details and product offerings." },
            { name: "Transactions", description: "Record of all financial transactions, including sales, refunds, and adjustments." }
        ];

        const itemsPerPage = 3;
        let currentPage = 1;

        function renderCards() {
            const cardList = document.getElementById('cardList');
            cardList.innerHTML = '';

            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedTables = tables.slice(start, end);

            paginatedTables.forEach(table => {
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `
                    <h2>${table.name}</h2>
                    <p>${table.description}</p>
                    <button class="select-button">Select this table</button>
                `;
                cardList.appendChild(card);
            });

            updatePaginationButtons();
        }

        function updatePaginationButtons() {
            const prevButton = document.getElementById('prevPage');
            const nextButton = document.getElementById('nextPage');

            prevButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === Math.ceil(tables.length / itemsPerPage);
        }

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderCards();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < Math.ceil(tables.length / itemsPerPage)) {
                currentPage++;
                renderCards();
            }
        });

        // Initial render
        renderCards();
    </script>
</body>
</html>