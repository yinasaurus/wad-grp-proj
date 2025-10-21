import mysql from 'mysql2';
import cors from 'cors';
import express from 'express';

const app = express();
app.use(cors());
app.use(express.json());

let connection = mysql.createConnection({
   host: "localhost",
   port: "3306",
   user: "greenbiz",
   password: "gbiz",
   database: "greenbiz",
});

connection.connect(function (err) {
    if (err) {
        console.log("Error in the connection");
        console.log(err);
    }
    else {
        console.log(`Database Connected`);

        // const query = 'SELECT * FROM businesses';

        // connection.query(query, (error, results, fields) => {
        //     if (error) {
        //         console.error('Query error:', error);
        //         return;
        //     }

        //     // results is an array of rows
        //     console.log('Fetched data:', results);
        // })

        // const userId = 5;
        // connection.query('SELECT * FROM users WHERE id = ?', [userId], (err, results) => {
        //     if (err) throw err;
        //     console.log(results);
        // });
    }
})

app.get('/api/users', (req, res) => {
  connection.query('SELECT * FROM businesses', (err, results) => {
    if (err) return res.status(500).send(err);
    res.json(results);
  });
});

app.get('/api/certs', (req, res) => {
  connection.query('SELECT * FROM certifications', (err, results) => {
    if (err) return res.status(500).send(err);
    res.json(results);
  });
});


app.listen(3000, () => console.log('Server running on http://localhost:3000'));