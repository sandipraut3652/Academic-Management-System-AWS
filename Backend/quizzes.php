<?php
session_start();
include("connect.php");

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$role      = $_SESSION['role'] ?? 'student';
$firstName = $_SESSION['firstName'] ?? 'User';

// ── 15 Programming / CS / DB / System Design Quizzes ──────────────────────
$quizzes = [
    [
        "id"=>"1","title"=>"SQL Fundamentals","subject"=>"Database",
        "icon"=>"fa-database","color"=>"#2563eb","accent"=>"#dbeafe",
        "questions"=>[
            ["q"=>"Which SQL clause filters rows AFTER grouping?","opts"=>["WHERE","HAVING","FILTER","LIMIT"],"ans"=>1],
            ["q"=>"Which JOIN returns all rows from both tables, filling NULLs where no match?","opts"=>["INNER JOIN","LEFT JOIN","RIGHT JOIN","FULL OUTER JOIN"],"ans"=>3],
            ["q"=>"What does the ACID property 'I' stand for?","opts"=>["Integration","Isolation","Indexing","Integrity"],"ans"=>1],
            ["q"=>"Which normal form eliminates transitive dependencies?","opts"=>["1NF","2NF","3NF","BCNF"],"ans"=>2],
            ["q"=>"Which SQL command removes a table and its structure entirely?","opts"=>["DELETE","TRUNCATE","DROP","REMOVE"],"ans"=>2],
        ]
    ],
    [
        "id"=>"2","title"=>"System Design Basics","subject"=>"System Design",
        "icon"=>"fa-layer-group","color"=>"#7c3aed","accent"=>"#ede9fe",
        "questions"=>[
            ["q"=>"What technique splits a large database into smaller pieces across servers?","opts"=>["Replication","Caching","Sharding","Indexing"],"ans"=>2],
            ["q"=>"Which component sits between clients and backend servers, routing requests?","opts"=>["CDN","Load Balancer","Message Queue","Cache"],"ans"=>1],
            ["q"=>"CAP theorem says a distributed system can guarantee at most how many properties simultaneously?","opts"=>["1","2","3","All three with tradeoffs"],"ans"=>1],
            ["q"=>"Which strategy serves repeated expensive computations from fast temporary storage?","opts"=>["Sharding","Caching","Replication","Partitioning"],"ans"=>1],
            ["q"=>"What does 'horizontal scaling' mean?","opts"=>["Upgrading CPU/RAM of one server","Adding more servers","Increasing disk space","Optimising queries"],"ans"=>1],
        ]
    ],
    [
        "id"=>"3","title"=>"Data Structures","subject"=>"CS Fundamentals",
        "icon"=>"fa-sitemap","color"=>"#059669","accent"=>"#d1fae5",
        "questions"=>[
            ["q"=>"Which data structure uses LIFO ordering?","opts"=>["Queue","Stack","Heap","Linked List"],"ans"=>1],
            ["q"=>"What is the average time complexity of HashMap lookup?","opts"=>["O(n)","O(log n)","O(1)","O(n²)"],"ans"=>2],
            ["q"=>"Which tree property ensures O(log n) operations in a BST?","opts"=>["Balanced height","Sorted leaves","Full nodes","Red-black coloring"],"ans"=>0],
            ["q"=>"Which data structure is ideal for BFS graph traversal?","opts"=>["Stack","Priority Queue","Queue","Deque"],"ans"=>2],
            ["q"=>"What is the space complexity of recursive Fibonacci (no memoisation)?","opts"=>["O(1)","O(n)","O(log n)","O(2ⁿ)"],"ans"=>1],
        ]
    ],
    [
        "id"=>"4","title"=>"OOP Concepts","subject"=>"Programming",
        "icon"=>"fa-cubes","color"=>"#d97706","accent"=>"#fef3c7",
        "questions"=>[
            ["q"=>"Which OOP principle restricts direct access to object internals?","opts"=>["Inheritance","Polymorphism","Encapsulation","Abstraction"],"ans"=>2],
            ["q"=>"A class that cannot be instantiated directly is called?","opts"=>["Final class","Static class","Abstract class","Interface"],"ans"=>2],
            ["q"=>"Method overloading is an example of which OOP concept?","opts"=>["Runtime polymorphism","Compile-time polymorphism","Encapsulation","Abstraction"],"ans"=>1],
            ["q"=>"Which SOLID principle states a class should have only one reason to change?","opts"=>["Open/Closed","Liskov Substitution","Single Responsibility","Interface Segregation"],"ans"=>2],
            ["q"=>"Which keyword calls the parent class constructor in most OOP languages?","opts"=>["this","base / super","parent","extends"],"ans"=>1],
        ]
    ],
    // [
    //     "id"=>"5","title"=>"Algorithms & Complexity","subject"=>"CS Fundamentals",
    //     "icon"=>"fa-code-branch","color"=>"#dc2626","accent"=>"#fee2e2",
    //     "questions"=>[
    //         ["q"=>"What is the time complexity of Merge Sort?","opts"=>["O(n)","O(n log n)","O(n²)","O(log n)"],"ans"=>1],
    //         ["q"=>"Which algorithm finds the shortest path in a weighted graph?","opts"=>["BFS","DFS","Dijkstra","Kruskal"],"ans"=>2],
    //         ["q"=>"What is the worst-case time of Quick Sort?","opts"=>["O(n log n)","O(n)","O(n²)","O(log n)"],"ans"=>2],
    //         ["q"=>"Dynamic Programming solves problems by?","opts"=>["Dividing into independent subproblems","Storing subproblem results","Random sampling","Graph traversal"],"ans"=>1],
    //         ["q"=>"Binary Search requires the input array to be?","opts"=>["Unsorted","Sorted","Unique elements","Fixed size"],"ans"=>1],
    //     ]
    // ],
    [
        "id"=>"6","title"=>"Database Design","subject"=>"Database",
        "icon"=>"fa-table","color"=>"#0891b2","accent"=>"#cffafe",
        "questions"=>[
            ["q"=>"An ER diagram 'crow's foot' notation represents?","opts"=>["Primary key","Many side of a relationship","One side of a relationship","Foreign key"],"ans"=>1],
            ["q"=>"Which key uniquely identifies each row in a table?","opts"=>["Foreign Key","Candidate Key","Primary Key","Composite Key"],"ans"=>2],
            ["q"=>"What does denormalization sacrifice to gain?","opts"=>["Storage for speed","Speed for storage","Consistency for availability","None"],"ans"=>0],
            ["q"=>"Which type of index is best for range queries?","opts"=>["Hash Index","B-Tree Index","Bitmap Index","Full-Text Index"],"ans"=>1],
            ["q"=>"One student has many courses AND one course has many students — this is?","opts"=>["One-to-One","One-to-Many","Many-to-One","Many-to-Many"],"ans"=>3],
        ]
    ],
    // [
    //     "id"=>"7","title"=>"Operating Systems","subject"=>"OS",
    //     "icon"=>"fa-server","color"=>"#7c3aed","accent"=>"#ede9fe",
    //     "questions"=>[
    //         ["q"=>"Which scheduling algorithm gives priority to the shortest job first?","opts"=>["FCFS","Round Robin","SJF","Priority Scheduling"],"ans"=>2],
    //         ["q"=>"What mechanism prevents two processes from entering a critical section simultaneously?","opts"=>["Semaphore","Interrupt","Context Switch","Paging"],"ans"=>0],
    //         ["q"=>"Virtual memory uses _____ to extend available RAM?","opts"=>["CPU Cache","Swap space on disk","Registers","ROM"],"ans"=>1],
    //         ["q"=>"A deadlock requires which four Coffman conditions?","opts"=>["Mutual exclusion, hold & wait, no preemption, circular wait","Starvation, priority, aging, preemption","Blocking, sleeping, waiting, running","None of the above"],"ans"=>0],
    //         ["q"=>"Which page replacement algorithm is optimal but impractical in real systems?","opts"=>["FIFO","LRU","Optimal (Belady's)","Clock"],"ans"=>2],
    //     ]
    // ],
    // [
    //     "id"=>"8","title"=>"Computer Networks","subject"=>"Networking",
    //     "icon"=>"fa-network-wired","color"=>"#16a34a","accent"=>"#dcfce7",
    //     "questions"=>[
    //         ["q"=>"Which OSI layer is responsible for end-to-end communication?","opts"=>["Network","Data Link","Transport","Application"],"ans"=>2],
    //         ["q"=>"TCP differs from UDP because TCP is?","opts"=>["Faster","Connection-oriented","Stateless","Broadcast-based"],"ans"=>1],
    //         ["q"=>"Which protocol translates domain names to IP addresses?","opts"=>["DHCP","ARP","DNS","FTP"],"ans"=>2],
    //         ["q"=>"What does a subnet mask define?","opts"=>["MAC address range","Network and host portions of an IP","Default gateway","Routing table"],"ans"=>1],
    //         ["q"=>"HTTP status code 404 means?","opts"=>["Server Error","Unauthorised","Not Found","Redirect"],"ans"=>2],
    //     ]
    // ],
    [
        "id"=>"9","title"=>"Python Programming","subject"=>"Programming",
        "icon"=>"fa-python","color"=>"#2563eb","accent"=>"#dbeafe",
        "questions"=>[
            ["q"=>"What does [x*2 for x in range(3)] produce?","opts"=>["[1,2,3]","[0,2,4]","[2,4,6]","[0,1,2]"],"ans"=>1],
            ["q"=>"Which keyword creates a generator function in Python?","opts"=>["return","async","yield","lambda"],"ans"=>2],
            ["q"=>"What is the output of bool('') in Python?","opts"=>["True","False","None","Error"],"ans"=>1],
            ["q"=>"Which Python type is immutable and ordered?","opts"=>["list","dict","set","tuple"],"ans"=>3],
            ["q"=>"What does *args allow in a Python function?","opts"=>["Keyword arguments only","Variable positional arguments","Default arguments","No arguments"],"ans"=>1],
        ]
    ],
    [
        "id"=>"10","title"=>"Web Development & REST","subject"=>"Web",
        "icon"=>"fa-globe","color"=>"#f97316","accent"=>"#ffedd5",
        "questions"=>[
            ["q"=>"REST API uses which HTTP method to fully update a resource?","opts"=>["POST","PATCH","PUT","GET"],"ans"=>2],
            ["q"=>"What does CORS stand for?","opts"=>["Cross-Origin Resource Sharing","Client-Origin Resource Sync","Cross-Origin Request Standard","Content-Origin Response Scheme"],"ans"=>0],
            ["q"=>"Which HTTP status code indicates successful resource creation?","opts"=>["200","201","204","301"],"ans"=>1],
            ["q"=>"JWT stands for?","opts"=>["Java Web Token","JSON Web Token","JavaScript Web Transfer","JSON Web Transfer"],"ans"=>1],
            ["q"=>"Which CSS value creates a flexible box layout?","opts"=>["display:block","display:grid","display:flex","display:inline"],"ans"=>2],
        ]
    ],
    // [
    //     "id"=>"11","title"=>"NoSQL Databases","subject"=>"Database",
    //     "icon"=>"fa-leaf","color"=>"#059669","accent"=>"#d1fae5",
    //     "questions"=>[
    //         ["q"=>"MongoDB stores data as?","opts"=>["Tables","XML","BSON documents","Key-Value pairs"],"ans"=>2],
    //         ["q"=>"Which NoSQL type is best for recommendation engines and social graphs?","opts"=>["Document","Key-Value","Column-family","Graph"],"ans"=>3],
    //         ["q"=>"Redis is primarily a?","opts"=>["Relational DB","In-memory key-value store","Document DB","Column DB"],"ans"=>1],
    //         ["q"=>"Eventual consistency prioritises?","opts"=>["Consistency over Availability","Availability over Consistency","Partition tolerance only","None"],"ans"=>1],
    //         ["q"=>"Cassandra is designed for?","opts"=>["OLTP transactions","High write throughput across nodes","Complex joins","Small datasets"],"ans"=>1],
    //     ]
    // ],
    // [
    //     "id"=>"12","title"=>"Design Patterns","subject"=>"Software Engineering",
    //     "icon"=>"fa-shapes","color"=>"#db2777","accent"=>"#fce7f3",
    //     "questions"=>[
    //         ["q"=>"The Singleton pattern ensures?","opts"=>["Multiple instances","Only one instance of a class","Fast object creation","Thread safety only"],"ans"=>1],
    //         ["q"=>"Which pattern defines a family of algorithms and makes them interchangeable?","opts"=>["Observer","Factory","Strategy","Decorator"],"ans"=>2],
    //         ["q"=>"The Observer pattern implements which relationship?","opts"=>["One-to-one","Many-to-many","One-to-many pub/sub","Parent-child"],"ans"=>2],
    //         ["q"=>"MVC stands for?","opts"=>["Model-View-Component","Module-View-Controller","Model-View-Controller","Module-Variable-Class"],"ans"=>2],
    //         ["q"=>"Which pattern wraps an object to add new behaviour without changing its class?","opts"=>["Adapter","Proxy","Decorator","Facade"],"ans"=>2],
    //     ]
    // ],
    // [
    //     "id"=>"13","title"=>"Cloud & DevOps","subject"=>"DevOps",
    //     "icon"=>"fa-cloud","color"=>"#0ea5e9","accent"=>"#e0f2fe",
    //     "questions"=>[
    //         ["q"=>"What does CI/CD stand for?","opts"=>["Continuous Integration / Continuous Deployment","Code Integration / Code Delivery","Continuous Inspection / Code Deployment","Continuous Integration / Code Design"],"ans"=>0],
    //         ["q"=>"Docker containers differ from VMs because containers?","opts"=>["Include a full OS","Share the host OS kernel","Are slower","Have more isolation"],"ans"=>1],
    //         ["q"=>"Kubernetes is used to?","opts"=>["Write code","Orchestrate containers","Monitor logs","Store data"],"ans"=>1],
    //         ["q"=>"IaaS provides?","opts"=>["Complete software apps","Virtualised computing infrastructure","Development platforms","Database services only"],"ans"=>1],
    //         ["q"=>"Which AWS service runs serverless functions?","opts"=>["EC2","S3","RDS","Lambda"],"ans"=>3],
    //     ]
    // ],
    // [
    //     "id"=>"14","title"=>"Cybersecurity","subject"=>"Security",
    //     "icon"=>"fa-shield-alt","color"=>"#dc2626","accent"=>"#fee2e2",
    //     "questions"=>[
    //         ["q"=>"SQL Injection is best prevented by?","opts"=>["Encrypting the database","Using prepared statements","Disabling SQL","Firewall rules only"],"ans"=>1],
    //         ["q"=>"HTTPS encrypts data using?","opts"=>["MD5","SHA-1","TLS/SSL","Base64"],"ans"=>2],
    //         ["q"=>"Which attack sends massive traffic to crash a server?","opts"=>["Phishing","Man-in-the-Middle","SQL Injection","DDoS"],"ans"=>3],
    //         ["q"=>"Which hashing algorithm is recommended for passwords?","opts"=>["MD5","SHA-1","bcrypt","SHA-256 only"],"ans"=>2],
    //         ["q"=>"XSS (Cross-Site Scripting) injects malicious?","opts"=>["SQL","Scripts into web pages","Emails","Network packets"],"ans"=>1],
    //     ]
    // ],
    // [
    //     "id"=>"15","title"=>"Git & Version Control","subject"=>"Tools",
    //     "icon"=>"fa-code-branch","color"=>"#f59e0b","accent"=>"#fef3c7",
    //     "questions"=>[
    //         ["q"=>"Which Git command saves staged changes to the local repository?","opts"=>["git push","git pull","git commit","git merge"],"ans"=>2],
    //         ["q"=>"What does git rebase do?","opts"=>["Deletes a branch","Reapplies commits on top of another base","Creates a new branch","Reverts the last commit"],"ans"=>1],
    //         ["q"=>"Which command undoes the last commit but keeps changes staged?","opts"=>["git revert HEAD","git reset --soft HEAD~1","git reset --hard HEAD~1","git stash"],"ans"=>1],
    //         ["q"=>"A pull request (PR) is used to?","opts"=>["Pull remote changes","Propose merging changes into another branch","Delete a branch","Clone a repository"],"ans"=>1],
    //         ["q"=>"git stash is used to?","opts"=>["Permanently delete changes","Temporarily shelve uncommitted changes","Push to remote","Create a new branch"],"ans"=>1],
    //     ]
    // ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes – EduPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --navy:#0f1e3d;--blue:#2563eb;--blue2:#1d4ed8;--indigo:#4f46e5;
            --green:#16a34a;--red:#dc2626;
            --bg:#eef2ff;--card:#fff;--border:#dde3f5;
            --text:#1e293b;--muted:#64748b;--radius:16px;
            --shadow:0 4px 28px rgba(37,99,235,.10);
        }
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

        /* NAVBAR */
        .navbar{background:var(--navy);padding:0 32px;display:flex;align-items:center;height:64px;gap:4px;box-shadow:0 2px 16px rgba(0,0,0,.22)}
        .navbar .brand{font-weight:700;font-size:18px;color:#fff;margin-right:24px;letter-spacing:-.4px;text-decoration:none}
        .navbar a{color:rgba(255,255,255,.7);text-decoration:none;font-size:14px;font-weight:500;padding:7px 14px;border-radius:8px;transition:.18s}
        .navbar a:hover,.navbar a.active{background:rgba(255,255,255,.13);color:#fff}
        .navbar .spacer{flex:1}

        /* HERO */
        .hero{background:linear-gradient(135deg,var(--navy) 0%,#1e3a8a 60%,var(--indigo) 100%);padding:44px 32px 52px;position:relative;overflow:hidden}
        .hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
        .hero-inner{max-width:980px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
        .hero-left .eyebrow{font-size:12px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px}
        .hero-left h1{font-size:32px;font-weight:800;color:#fff;line-height:1.2;margin-bottom:6px}
        .hero-left h1 span{background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-left .sub{font-size:14px;color:rgba(255,255,255,.55)}
        .hero-stats{display:flex;gap:16px;flex-wrap:wrap}
        .hstat{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:14px 20px;text-align:center;backdrop-filter:blur(8px)}
        .hstat .num{font-size:26px;font-weight:800;color:#fff}
        .hstat .lbl{font-size:11px;font-weight:600;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.8px;margin-top:2px}

        /* PAGE */
        .page{max-width:980px;margin:38px auto;padding:0 20px 80px}
        .section-title{font-size:16px;font-weight:700;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .badge{background:var(--blue);color:#fff;font-size:12px;font-weight:700;border-radius:20px;padding:2px 9px}

        /* FILTER BAR */
        .filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:22px;flex-wrap:wrap}
        .filter-btn{padding:7px 16px;border-radius:20px;border:1.5px solid var(--border);background:#fff;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:.18s}
        .filter-btn:hover,.filter-btn.active{background:var(--blue);color:#fff;border-color:var(--blue)}
        .search-wrap{position:relative;margin-left:auto}
        .search-wrap input{border:1.5px solid var(--border);border-radius:9px;padding:8px 14px 8px 36px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.18s;width:200px}
        .search-wrap input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px}

        /* QUIZ GRID */
        .quiz-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
        .quiz-card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;cursor:pointer;transition:box-shadow .2s,transform .2s;animation:fadeUp .4s ease both}
        .quiz-card:hover{box-shadow:0 12px 40px rgba(37,99,235,.18);transform:translateY(-4px)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .card-stripe{height:5px}
        .card-body{padding:20px 20px 14px}
        .card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
        .card-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .card-subject-pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase}
        .card-title{font-size:15px;font-weight:700;color:var(--navy);margin-bottom:10px;line-height:1.35}
        .card-meta{display:flex;gap:10px}
        .meta-tag{display:flex;align-items:center;gap:4px;font-size:12px;color:var(--muted);font-weight:500}
        .meta-tag i{font-size:11px}
        .card-footer{padding:12px 20px;border-top:1px solid var(--border);background:#fafbff}
        .start-btn{width:100%;padding:9px;border:none;border-radius:9px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;color:#fff;transition:opacity .2s,transform .1s}
        .start-btn:hover{opacity:.88;transform:scale(.99)}

        /* MODAL */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,30,61,.6);z-index:1000;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(4px)}
        .modal-overlay.active{display:flex}
        .quiz-modal{background:#fff;border-radius:20px;width:100%;max-width:560px;max-height:92vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.25);animation:popUp .22s ease}
        @keyframes popUp{from{transform:scale(.93) translateY(20px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
        .modal-stripe{height:7px;border-radius:20px 20px 0 0}
        .modal-header{padding:22px 26px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
        .modal-subject{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;color:var(--muted)}
        .modal-title{font-size:20px;font-weight:800;color:var(--navy)}
        .modal-close{background:#f1f5f9;border:none;color:var(--muted);width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.18s}
        .modal-close:hover{background:#e2e8f0;color:var(--text)}
        .modal-body{padding:22px 26px}

        /* Progress */
        .progress-wrap{display:flex;align-items:center;gap:10px;margin-bottom:20px}
        .prog-bg{flex:1;height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden}
        .prog-fill{height:100%;border-radius:10px;transition:width .4s ease}
        .prog-label{font-size:12px;font-weight:700;color:var(--muted);white-space:nowrap}

        /* Question */
        .q-num{font-size:11px;font-weight:700;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:6px}
        .q-text{font-size:17px;font-weight:700;color:var(--navy);margin-bottom:18px;line-height:1.45}

        /* Options */
        .options{display:flex;flex-direction:column;gap:9px;margin-bottom:18px}
        .opt-btn{background:#f8faff;border:2px solid var(--border);border-radius:11px;padding:12px 14px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;color:var(--text);cursor:pointer;text-align:left;display:flex;align-items:center;gap:11px;transition:all .15s}
        .opt-btn:hover:not(:disabled){border-color:var(--blue);background:#eff6ff;color:var(--blue)}
        .opt-lbl{width:26px;height:26px;border-radius:7px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;transition:.15s}
        .opt-btn.correct{border-color:#16a34a;background:#f0fdf4}
        .opt-btn.correct .opt-lbl{background:#16a34a;color:#fff}
        .opt-btn.wrong{border-color:#dc2626;background:#fef2f2}
        .opt-btn.wrong .opt-lbl{background:#dc2626;color:#fff}
        .opt-btn:disabled{cursor:not-allowed}

        /* Feedback */
        .feedback{display:none;padding:11px 14px;border-radius:9px;font-size:13px;font-weight:600;margin-bottom:14px;align-items:center;gap:8px}
        .feedback.show{display:flex}
        .feedback.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
        .feedback.err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}

        /* Next btn */
        .next-btn{width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:700;cursor:pointer;display:none;align-items:center;justify-content:center;gap:7px;transition:opacity .2s}
        .next-btn.show{display:flex}
        .next-btn:hover{opacity:.9}

        /* Result */
        .result-screen{display:none;text-align:center;padding:10px 0}
        .result-screen.show{display:block}
        .result-emoji{font-size:54px;margin-bottom:12px}
        .result-score{font-size:48px;font-weight:800;color:var(--navy);margin-bottom:4px}
        .result-sub{color:var(--muted);font-size:14px;margin-bottom:6px}
        .result-msg{font-size:17px;font-weight:700;margin-bottom:26px}
        .result-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .btn-secondary{padding:10px 22px;border:2px solid var(--border);background:#fff;color:var(--text);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.18s}
        .btn-secondary:hover{border-color:var(--blue);color:var(--blue)}
        .btn-primary{padding:10px 22px;border:none;background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px}
        .btn-primary:hover{opacity:.9}

        /* Empty */
        .empty{text-align:center;padding:60px 20px;color:var(--muted);display:none}
        .empty i{font-size:40px;margin-bottom:12px;display:block;opacity:.35}

        @media(max-width:640px){
            .navbar{padding:0 14px;gap:2px;flex-wrap:wrap;height:auto;padding:10px 14px}
            .hero{padding:28px 18px 36px}
            .hero-inner{flex-direction:column;align-items:flex-start}
            .quiz-grid{grid-template-columns:1fr}
            .modal-body,.modal-header{padding:16px 18px}
            .filter-bar{gap:6px}
            .search-wrap{margin-left:0;width:100%}
            .search-wrap input{width:100%}
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <span class="brand">📚 EduPortal</span>
    <?php if($role==='teacher'): ?>
        <a href="teacher-dashboard.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php">Homework</a>
        <a href="meetings.php">Meetings</a>
        <a href="announcements.php">Announcements</a>
        <a href="attendance.php">Attendance</a>
    <?php else: ?>
        <a href="homepage.php">Home</a>
        <a href="study-materials.php">Materials</a>
        <a href="homework.php">Homework</a>
    <?php endif; ?>
    <a href="quizzes.php" class="active">Quiz</a>
    <div class="spacer"></div>
    <a href="logout.php">👋 Logout</a>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div class="hero-left">
            <div class="eyebrow">Programming Quizzes</div>
            <h1>Test Your <span>CS &amp; Dev Skills</span> 🧠</h1>
            <div class="sub">SQL · System Design · Algorithms · OOP · Networks · DevOps &amp; more</div>
        </div>
        <div class="hero-stats">
            <div class="hstat"><div class="num"><?= count($quizzes) ?></div><div class="lbl">Quizzes</div></div>
            <div class="hstat"><div class="num"><?= count($quizzes)*5 ?></div><div class="lbl">Questions</div></div>
            <div class="hstat"><div class="num">10</div><div class="lbl">Subjects</div></div>
        </div>
    </div>
</div>

<!-- PAGE -->
<div class="page">

    <div class="section-title">
        ⚡ All Quizzes
        <span class="badge"><?= count($quizzes) ?></span>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterQuiz('all',this)">All</button>
        <button class="filter-btn" onclick="filterQuiz('Database',this)">Database</button>
        <button class="filter-btn" onclick="filterQuiz('System Design',this)">System Design</button>
        <button class="filter-btn" onclick="filterQuiz('CS Fundamentals',this)">CS Fundamentals</button>
        <button class="filter-btn" onclick="filterQuiz('Programming',this)">Programming</button>
        <button class="filter-btn" onclick="filterQuiz('Web',this)">Web</button>
        <button class="filter-btn" onclick="filterQuiz('DevOps',this)">DevOps</button>
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" placeholder="Search quizzes…" oninput="searchQuiz(this.value)">
        </div>
    </div>

    <!-- Grid -->
    <div class="quiz-grid" id="quizGrid">
        <?php foreach($quizzes as $idx => $q): ?>
        <div class="quiz-card"
             data-subject="<?= htmlspecialchars($q['subject']) ?>"
             data-title="<?= strtolower(htmlspecialchars($q['title'])) ?>"
             style="animation-delay:<?= $idx*0.04 ?>s">
            <div class="card-stripe" style="background:<?= $q['color'] ?>"></div>
            <div class="card-body">
                <div class="card-top">
                    <div class="card-icon" style="background:<?= $q['accent'] ?>;color:<?= $q['color'] ?>">
                        <i class="fas <?= $q['icon'] ?>"></i>
                    </div>
                    <span class="card-subject-pill" style="background:<?= $q['accent'] ?>;color:<?= $q['color'] ?>">
                        <?= htmlspecialchars($q['subject']) ?>
                    </span>
                </div>
                <div class="card-title"><?= htmlspecialchars($q['title']) ?></div>
                <div class="card-meta">
                    <span class="meta-tag"><i class="fas fa-question-circle"></i> 5 Questions</span>
                    <span class="meta-tag"><i class="fas fa-clock"></i> ~3 min</span>
                    <span class="meta-tag"><i class="fas fa-signal"></i> Medium</span>
                </div>
            </div>
            <div class="card-footer">
                <button class="start-btn" style="background:<?= $q['color'] ?>" onclick="openQuiz(<?= $idx ?>)">
                    <i class="fas fa-play"></i> Start Quiz
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="empty" id="emptyState">
        <i class="fas fa-search"></i>
        No quizzes match your search.
    </div>

</div>

<!-- QUIZ MODAL -->
<div class="modal-overlay" id="quizModal">
    <div class="quiz-modal">
        <div class="modal-stripe" id="modalStripe"></div>
        <div class="modal-header">
            <div>
                <div class="modal-subject" id="modalSubject"></div>
                <div class="modal-title"   id="modalTitle"></div>
            </div>
            <button class="modal-close" onclick="closeQuiz()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <!-- Quiz screen -->
            <div id="quizScreen">
                <div class="progress-wrap">
                    <div class="prog-bg"><div class="prog-fill" id="progFill" style="width:0%"></div></div>
                    <span class="prog-label" id="progLabel">1 / 5</span>
                </div>
                <div class="q-num"  id="qNum">QUESTION 1</div>
                <div class="q-text" id="qText"></div>
                <div class="options" id="optList"></div>
                <div class="feedback" id="feedback"></div>
                <button class="next-btn" id="nextBtn" onclick="nextQ()">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <!-- Result screen -->
            <div class="result-screen" id="resultScreen">
                <div class="result-emoji" id="rEmoji"></div>
                <div class="result-score" id="rScore"></div>
                <div class="result-sub">out of 5 correct</div>
                <div class="result-msg"   id="rMsg"></div>
                <div class="result-actions">
                    <button class="btn-secondary" onclick="retryQuiz()"><i class="fas fa-redo"></i> Retry</button>
                    <button class="btn-primary"   onclick="closeQuiz()"><i class="fas fa-th"></i> All Quizzes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const QUIZZES = <?= json_encode($quizzes) ?>;
const LABELS  = ['A','B','C','D'];
let cur=null, qIdx=0, score=0, answered=false;

function openQuiz(idx){
    cur=QUIZZES[idx]; qIdx=0; score=0; answered=false;
    document.getElementById('modalStripe').style.background = cur.color;
    document.getElementById('progFill').style.background    = cur.color;
    document.getElementById('modalSubject').textContent     = cur.subject;
    document.getElementById('modalTitle').textContent       = cur.title;
    document.getElementById('quizScreen').style.display     = 'block';
    document.getElementById('resultScreen').classList.remove('show');
    document.getElementById('quizModal').classList.add('active');
    loadQ();
}
function closeQuiz(){ document.getElementById('quizModal').classList.remove('active'); }

function loadQ(){
    answered=false;
    const q=cur.questions[qIdx], total=cur.questions.length;
    document.getElementById('progFill').style.width  = (qIdx/total*100)+'%';
    document.getElementById('progLabel').textContent = (qIdx+1)+' / '+total;
    document.getElementById('qNum').textContent      = 'QUESTION '+(qIdx+1);
    document.getElementById('qText').textContent     = q.q;
    const ol=document.getElementById('optList');
    ol.innerHTML='';
    q.opts.forEach((opt,i)=>{
        const b=document.createElement('button');
        b.className='opt-btn';
        b.innerHTML=`<span class="opt-lbl">${LABELS[i]}</span>${opt}`;
        b.onclick=()=>pick(i,q.ans);
        ol.appendChild(b);
    });
    const fb=document.getElementById('feedback');
    fb.className='feedback'; fb.innerHTML='';
    document.getElementById('nextBtn').classList.remove('show');
}

function pick(sel,correct){
    if(answered) return;
    answered=true;
    document.querySelectorAll('.opt-btn').forEach(b=>b.disabled=true);
    const btns=document.querySelectorAll('.opt-btn');
    const fb=document.getElementById('feedback');
    if(sel===correct){
        score++;
        btns[sel].classList.add('correct');
        fb.className='feedback ok show';
        fb.innerHTML='<i class="fas fa-check-circle"></i> Correct!';
    } else {
        btns[sel].classList.add('wrong');
        btns[correct].classList.add('correct');
        fb.className='feedback err show';
        fb.innerHTML=`<i class="fas fa-times-circle"></i> Incorrect — correct: <strong>${cur.questions[qIdx].opts[correct]}</strong>`;
    }
    const nb=document.getElementById('nextBtn');
    const last=(qIdx===cur.questions.length-1);
    nb.innerHTML=last?'<i class="fas fa-flag-checkered"></i> See Results':'Next <i class="fas fa-arrow-right"></i>';
    nb.classList.add('show');
}

function nextQ(){
    qIdx++;
    if(qIdx>=cur.questions.length) showResult();
    else loadQ();
}

function showResult(){
    document.getElementById('quizScreen').style.display='none';
    document.getElementById('resultScreen').classList.add('show');
    const pct=(score/cur.questions.length)*100;
    let emoji,msg,color;
    if(pct===100){emoji='🏆';msg='Perfect Score!';color='#f59e0b';}
    else if(pct>=80){emoji='🌟';msg='Excellent Work!';color='#16a34a';}
    else if(pct>=60){emoji='👍';msg='Good Job! Keep it up.';color='#2563eb';}
    else if(pct>=40){emoji='📚';msg='Keep Studying!';color='#f97316';}
    else{emoji='💪';msg="Don't Give Up — Try Again!";color='#dc2626';}
    document.getElementById('rEmoji').textContent=emoji;
    document.getElementById('rScore').textContent=score+'/'+cur.questions.length;
    document.getElementById('rScore').style.color=color;
    document.getElementById('rMsg').textContent=msg;
    document.getElementById('rMsg').style.color=color;
}

function retryQuiz(){
    qIdx=0;score=0;answered=false;
    document.getElementById('quizScreen').style.display='block';
    document.getElementById('resultScreen').classList.remove('show');
    loadQ();
}

function filterQuiz(subject,btn){
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.quiz-card').forEach(c=>{
        c.style.display=(subject==='all'||c.dataset.subject===subject)?'':'none';
    });
    checkEmpty();
}
function searchQuiz(val){
    const v=val.toLowerCase();
    document.querySelectorAll('.quiz-card').forEach(c=>{
        c.style.display=(c.dataset.title.includes(v)||c.dataset.subject.toLowerCase().includes(v))?'':'none';
    });
    checkEmpty();
}
function checkEmpty(){
    const any=[...document.querySelectorAll('.quiz-card')].some(c=>c.style.display!=='none');
    document.getElementById('emptyState').style.display=any?'none':'block';
}

document.getElementById('quizModal').addEventListener('click',e=>{
    if(e.target===document.getElementById('quizModal')) closeQuiz();
});
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeQuiz(); });
</script>
</body>
</html>