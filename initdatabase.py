import sys
import mysql.connector

mydb = mysql.connector.connect(
  user="test",
  passwd=sys.argv[1]
)

mycursor = mydb.cursor()

mycursor.execute("CREATE DATABASE d2")
mycursor.execute("USE d1")
mycursor.execute("CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), gid VARCHAR(255))")
sql = "INSERT INTO users (name, gid) VALUES (%s, %s)"
val = ("alex", "asdfasf")
mycursor.execute(sql, val)
mydb.commit()
print(mycursor.rowcount, "record inserted.")
